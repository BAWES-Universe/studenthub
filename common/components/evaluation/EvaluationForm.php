<?php

namespace common\components\evaluation;

/**
 * Structure, validation, and sanitizing for one candidate evaluation.
 * Property ids stay stable across rename and reorder. Presets store schema only.
 */
class EvaluationForm
{
    public const TYPES = [
        'text',
        'notes',
        'number',
        'select',
        'multi_select',
        'status',
        'date',
        'staff',
        'url',
        'email',
        'phone',
        'checkbox',
    ];

    public const SELECT_TYPES = ['select', 'multi_select', 'status'];

    public const COLORS = [
        'gray',
        'brown',
        'orange',
        'yellow',
        'green',
        'blue',
        'purple',
        'pink',
        'red',
    ];

    public const MAX_PROPERTIES = 40;
    public const MAX_OPTIONS = 40;
    public const MAX_TEXT = 4000;
    public const MAX_NOTES = 20000;

    /**
     * @param mixed $schema
     * @return array{ok: bool, schema: array<string, mixed>, errors: array<int, array<string, mixed>>}
     */
    public static function normalizeSchema($schema)
    {
        if (!is_array($schema) || !isset($schema['properties']) || !is_array($schema['properties'])) {
            return self::fail('The evaluation properties are not valid.');
        }
        if (array_keys($schema['properties']) !== range(0, count($schema['properties']) - 1) && $schema['properties'] !== []) {
            return self::fail('The evaluation properties are not valid.');
        }
        if (count($schema['properties']) > self::MAX_PROPERTIES) {
            return self::fail('An evaluation can have at most ' . self::MAX_PROPERTIES . ' properties.');
        }

        $properties = [];
        $seen = [];
        foreach ($schema['properties'] as $property) {
            $normalized = self::normalizeProperty($property, $seen);
            if (empty($normalized['ok'])) {
                return $normalized;
            }
            $properties[] = $normalized['property'];
            $seen[$normalized['property']['id']] = true;
        }

        return [
            'ok' => true,
            'schema' => ['properties' => $properties],
            'errors' => [],
        ];
    }

    /**
     * @param array<string, mixed> $current stored evaluation, or exists=false for a new one
     * @param array<string, mixed> $input
     * @param array<int, int>|null $knownStaffIds null skips the staff-directory check
     * @return array<string, mixed>
     */
    public static function prepareSave(array $current, array $input, $knownStaffIds = null)
    {
        $exists = !empty($current['exists']);
        if ($exists && !empty($current['legacy'])) {
            return self::fail('This evaluation uses the earlier notes format.');
        }
        if ($exists && (int) ($input['expected_revision'] ?? -1) !== (int) $current['revision']) {
            return [
                'ok' => false,
                'conflict' => true,
                'errors' => [[
                    'property_id' => null,
                    'message' => 'Someone else saved this evaluation after you opened it. Your unsaved entries are still on this page.',
                ]],
            ];
        }

        $mode = isset($input['mode']) ? (string) $input['mode'] : 'draft';
        if (!in_array($mode, ['draft', 'complete', 'correction'], true)) {
            return self::fail('Choose save draft, complete, or save changes.');
        }

        $currentStatus = $exists ? (string) ($current['form_status'] ?? 'draft') : 'draft';
        if ($mode === 'draft' && $currentStatus === 'completed') {
            return self::fail('This evaluation is completed. Use Edit and Save changes.');
        }
        if ($mode === 'correction' && $currentStatus !== 'completed') {
            return self::fail('Save changes is for completed evaluations.');
        }
        if ($mode === 'complete' && $currentStatus === 'completed') {
            return self::fail('This evaluation is already completed. Use Save changes.');
        }

        $schemaResult = self::normalizeSchema(isset($input['schema']) ? $input['schema'] : ['properties' => []]);
        if (empty($schemaResult['ok'])) {
            return $schemaResult;
        }

        $previousSchema = $exists && isset($current['schema']) ? $current['schema'] : ['properties' => []];
        $previousAnswers = $exists && isset($current['answers']) && is_array($current['answers']) ? $current['answers'] : [];
        $guard = self::guardStructure(
            $previousSchema,
            $schemaResult['schema'],
            $previousAnswers,
            self::idList(isset($input['confirm_clear_properties']) ? $input['confirm_clear_properties'] : []),
            self::idList(isset($input['confirm_remove_properties']) ? $input['confirm_remove_properties'] : [])
        );
        if (empty($guard['ok'])) {
            return [
                'ok' => false,
                'conflict' => false,
                'errors' => $guard['errors'],
            ];
        }

        $require = $mode === 'complete' || $mode === 'correction';
        $answersResult = self::validateAnswers(
            $schemaResult['schema'],
            isset($input['answers']) && is_array($input['answers']) ? $input['answers'] : [],
            $require,
            $knownStaffIds
        );
        if (empty($answersResult['ok'])) {
            return $answersResult;
        }

        $title = trim((string) (isset($input['title']) ? $input['title'] : ''));
        if ($title === '') {
            if ($mode === 'draft') {
                $title = 'Untitled evaluation';
            } else {
                return self::fail('Add a title before completing this evaluation.', 'title');
            }
        }
        if (strlen($title) > 200) {
            return self::fail('The title is too long.', 'title');
        }

        $date = isset($input['evaluation_date']) ? $input['evaluation_date'] : null;
        if ($date === '' || $date === null) {
            $date = null;
        }
        if ($date !== null && !self::validDate($date)) {
            return self::fail('Enter the evaluation date as YYYY-MM-DD.', 'evaluation_date');
        }
        if ($require && $date === null) {
            return self::fail('Add the evaluation date before completing.', 'evaluation_date');
        }

        $status = $mode === 'draft' ? 'draft' : 'completed';
        $revision = $exists ? ((int) $current['revision']) + 1 : 1;

        return [
            'ok' => true,
            'conflict' => false,
            'errors' => [],
            'state' => [
                'title' => $title,
                'evaluation_date' => $date,
                'form_status' => $status,
                'schema' => $schemaResult['schema'],
                'answers' => $answersResult['answers'],
                'revision' => $revision,
                'action' => $mode === 'correction' ? 'correction' : $mode,
                'success_message' => self::successMessage($mode),
            ],
        ];
    }

    /**
     * Schema only. Answers, dates, and people are not part of a preset.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public static function presetSchema(array $schema)
    {
        return self::normalizeSchema($schema);
    }

    /**
     * @param array<string, mixed> $preset
     * @return array<string, mixed>
     */
    public static function blankEvaluationFromPreset(array $preset)
    {
        $schema = self::normalizeSchema(isset($preset['schema']) ? $preset['schema'] : []);
        if (empty($schema['ok'])) {
            return $schema;
        }

        return [
            'ok' => true,
            'title' => isset($preset['name']) ? (string) $preset['name'] : 'Untitled evaluation',
            'evaluation_date' => null,
            'schema' => $schema['schema'],
            'answers' => [],
            'preset_uuid' => isset($preset['preset_uuid']) ? $preset['preset_uuid'] : null,
            'preset_name' => isset($preset['name']) ? (string) $preset['name'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $answers
     * @return array<int, int>
     */
    public static function collectStaffIds(array $schema, array $answers)
    {
        $ids = [];
        $properties = isset($schema['properties']) && is_array($schema['properties']) ? $schema['properties'] : [];
        foreach ($properties as $property) {
            if (!is_array($property) || ($property['type'] ?? null) !== 'staff') {
                continue;
            }
            $id = isset($property['id']) ? $property['id'] : null;
            if ($id === null || !isset($answers[$id]) || $answers[$id] === null || $answers[$id] === '') {
                continue;
            }
            $ids[] = (int) $answers[$id];
        }
        return array_values(array_unique($ids));
    }

    /**
     * @param mixed $value
     * @return array{ok: bool, html?: string, errors?: array<int, array<string, mixed>>}
     */
    public static function sanitizeNotes($value)
    {
        if (!is_string($value)) {
            return ['ok' => false, 'errors' => [[
                'property_id' => null,
                'message' => 'Notes must be text.',
            ]]];
        }
        $value = str_replace("\0", '', $value);
        if (trim($value) === '') {
            return ['ok' => true, 'html' => ''];
        }
        if (strlen($value) > 100000) {
            $value = substr($value, 0, 100000);
        }

        $allowed = [
            'p' => true,
            'br' => true,
            'strong' => true,
            'b' => true,
            'em' => true,
            'ul' => true,
            'ol' => true,
            'li' => true,
        ];
        $blocked = ['script' => true, 'style' => true, 'iframe' => true, 'object' => true, 'embed' => true];

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>' . $value . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementsByTagName('div')->item(0);
        if (!$root) {
            return ['ok' => true, 'html' => ''];
        }
        self::sanitizeNode($root, $allowed, $blocked, true);

        $html = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $html .= $document->saveHTML($child);
        }
        $html = trim($html);
        if (strlen($html) > self::MAX_NOTES) {
            return ['ok' => false, 'errors' => [[
                'property_id' => null,
                'message' => 'Notes are too long.',
            ]]];
        }

        return ['ok' => true, 'html' => $html];
    }

    /**
     * @param mixed $type
     * @param mixed $value
     */
    public static function answerIsEmpty($type, $value)
    {
        if ($type === 'checkbox') {
            return $value !== true && $value !== 1 && $value !== '1';
        }
        if ($type === 'multi_select') {
            return !is_array($value) || $value === [];
        }
        if ($type === 'number') {
            return $value === null || $value === '';
        }
        if ($type === 'notes') {
            return self::notesAreBlank($value);
        }
        if (is_string($value)) {
            return trim(self::plainText($value)) === '';
        }
        return $value === null;
    }

    /**
     * @param array<string, mixed> $previousSchema
     * @param array<string, mixed> $nextSchema
     * @param array<string, mixed> $previousAnswers
     * @param array<int, string> $confirmClear
     * @param array<int, string> $confirmRemove
     * @return array{ok: bool, errors: array<int, array<string, mixed>>, cleared: array<string, bool>}
     */
    private static function guardStructure(array $previousSchema, array $nextSchema, array $previousAnswers, array $confirmClear, array $confirmRemove)
    {
        $errors = [];
        $cleared = [];
        $nextById = [];
        foreach ($nextSchema['properties'] as $property) {
            $nextById[$property['id']] = $property;
        }

        $previousProperties = isset($previousSchema['properties']) ? $previousSchema['properties'] : [];
        foreach ($previousProperties as $previous) {
            $id = $previous['id'];
            $answer = array_key_exists($id, $previousAnswers) ? $previousAnswers[$id] : null;
            if (self::answerIsEmpty($previous['type'], $answer)) {
                continue;
            }
            if (!isset($nextById[$id])) {
                if (!in_array($id, $confirmRemove, true)) {
                    $errors[] = [
                        'property_id' => $id,
                        'message' => 'Remove "' . $previous['name'] . '" only after confirming its answer will be deleted.',
                    ];
                }
                continue;
            }
            $next = $nextById[$id];
            if ($next['type'] !== $previous['type']) {
                if (!in_array($id, $confirmClear, true)) {
                    $errors[] = [
                        'property_id' => $id,
                        'message' => 'Changing the type of "' . $previous['name'] . '" would clear its answer. Confirm to continue.',
                    ];
                } else {
                    $cleared[$id] = true;
                }
                continue;
            }
            if (in_array($previous['type'], self::SELECT_TYPES, true)) {
                $optionIds = array_column($next['options'], 'id');
                $selected = $previous['type'] === 'multi_select' ? (array) $answer : [$answer];
                foreach ($selected as $selectedId) {
                    if ($selectedId === null || $selectedId === '') {
                        continue;
                    }
                    if (!in_array($selectedId, $optionIds, true)) {
                        if (!in_array($id, $confirmClear, true)) {
                            $errors[] = [
                                'property_id' => $id,
                                'message' => 'An option still selected on "' . $previous['name'] . '" was removed. Confirm to clear that selection.',
                            ];
                        } else {
                            $cleared[$id] = true;
                        }
                        break;
                    }
                }
            }
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'cleared' => $cleared,
        ];
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $answers
     * @param array<int, int>|null $knownStaffIds
     * @return array<string, mixed>
     */
    private static function validateAnswers(array $schema, array $answers, $require, $knownStaffIds)
    {
        $stored = [];
        $errors = [];
        foreach ($schema['properties'] as $property) {
            $id = $property['id'];
            $value = array_key_exists($id, $answers) ? $answers[$id] : null;
            if (!self::answerIsEmpty($property['type'], $value)) {
                $checked = self::normalizeAnswer($property, $value, $knownStaffIds);
                if (empty($checked['ok'])) {
                    $checked['errors'][0]['property_id'] = $id;
                    $errors[] = $checked['errors'][0];
                    $stored[$id] = $value;
                    continue;
                }
                $value = $checked['value'];
            }

            if (self::answerIsEmpty($property['type'], $value)) {
                if ($require && !empty($property['required'])) {
                    $errors[] = [
                        'property_id' => $id,
                        'message' => $property['name'] . ' is required.',
                    ];
                }
                $stored[$id] = self::emptyValue($property['type']);
                continue;
            }
            $stored[$id] = $value;
        }

        if ($errors !== []) {
            return [
                'ok' => false,
                'conflict' => false,
                'errors' => $errors,
            ];
        }

        return [
            'ok' => true,
            'answers' => $stored,
            'errors' => [],
        ];
    }

    /**
     * @param array<string, mixed> $property
     * @param mixed $value
     * @param array<int, int>|null $knownStaffIds
     * @return array<string, mixed>
     */
    private static function normalizeAnswer(array $property, $value, $knownStaffIds)
    {
        $type = $property['type'];
        if ($type === 'text') {
            if (!is_string($value) && !is_numeric($value)) {
                return self::fail('Enter text for ' . $property['name'] . '.');
            }
            $text = trim(strip_tags((string) $value));
            if (strlen($text) > self::MAX_TEXT) {
                return self::fail($property['name'] . ' is too long.');
            }
            return ['ok' => true, 'value' => $text];
        }
        if ($type === 'notes') {
            $notes = self::sanitizeNotes(is_string($value) ? $value : '');
            if (empty($notes['ok'])) {
                $notes['errors'][0]['message'] = $property['name'] . ' could not be saved.';
                return $notes;
            }
            if ($notes['html'] === '') {
                return ['ok' => true, 'value' => ''];
            }
            return ['ok' => true, 'value' => $notes['html']];
        }
        if ($type === 'number') {
            if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
                return self::fail('Enter a number for ' . $property['name'] . '.');
            }
            $number = $value + 0;
            if (!is_finite($number) || abs($number) > 1000000000000) {
                return self::fail($property['name'] . ' is not a valid number.');
            }
            return ['ok' => true, 'value' => $number];
        }
        if ($type === 'select' || $type === 'status') {
            if (!is_string($value) || !self::optionExists($property, $value)) {
                return self::fail('Choose an option for ' . $property['name'] . '.');
            }
            return ['ok' => true, 'value' => $value];
        }
        if ($type === 'multi_select') {
            if (!is_array($value)) {
                return self::fail('Choose options for ' . $property['name'] . '.');
            }
            $chosen = [];
            foreach ($value as $optionId) {
                if (!is_string($optionId) || !self::optionExists($property, $optionId) || in_array($optionId, $chosen, true)) {
                    return self::fail('Choose options for ' . $property['name'] . '.');
                }
                $chosen[] = $optionId;
            }
            return ['ok' => true, 'value' => $chosen];
        }
        if ($type === 'date') {
            if (!is_string($value) || !self::validDate($value)) {
                return self::fail('Enter a valid date for ' . $property['name'] . '.');
            }
            return ['ok' => true, 'value' => $value];
        }
        if ($type === 'staff') {
            if (!is_int($value) && !(is_string($value) && preg_match('/^[1-9][0-9]*$/', $value))) {
                return self::fail('Choose a staff member for ' . $property['name'] . '.');
            }
            $staffId = (int) $value;
            if ($knownStaffIds !== null && !in_array($staffId, array_map('intval', $knownStaffIds), true)) {
                return self::fail('Choose a current staff member for ' . $property['name'] . '.');
            }
            return ['ok' => true, 'value' => $staffId];
        }
        if ($type === 'url') {
            if (!is_string($value) || !self::validUrl($value)) {
                return self::fail('Enter an http or https link for ' . $property['name'] . '.');
            }
            return ['ok' => true, 'value' => $value];
        }
        if ($type === 'email') {
            if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $value) || strlen($value) > 254) {
                return self::fail('Enter an email address for ' . $property['name'] . '.');
            }
            return ['ok' => true, 'value' => $value];
        }
        if ($type === 'phone') {
            if (is_string($value)) {
                $value = trim($value);
            }
            if (!is_string($value) || !preg_match('/^\+?[0-9][0-9 ().-]{5,31}$/', $value)) {
                return self::fail('Enter a phone number for ' . $property['name'] . '.');
            }
            return ['ok' => true, 'value' => $value];
        }
        if ($type === 'checkbox') {
            $checked = $value === true || $value === 1 || $value === '1';
            return ['ok' => true, 'value' => $checked];
        }

        return self::fail('Unsupported property type.');
    }

    /**
     * @param mixed $property
     * @param array<string, bool> $seen
     * @return array<string, mixed>
     */
    private static function normalizeProperty($property, array $seen)
    {
        if (!is_array($property)) {
            return self::fail('A property is not valid.');
        }
        $id = isset($property['id']) ? (string) $property['id'] : '';
        if (!preg_match('/^prop_[a-z0-9]{8,64}$/', $id) || isset($seen[$id])) {
            return self::fail('A property identifier is missing or duplicated.');
        }
        $name = trim((string) (isset($property['name']) ? $property['name'] : ''));
        if ($name === '' || strlen($name) > 80) {
            return self::fail('Each property needs a name of 80 characters or fewer.', $id);
        }
        $type = isset($property['type']) ? (string) $property['type'] : '';
        if (!in_array($type, self::TYPES, true)) {
            return self::fail('Choose a supported type for "' . $name . '".', $id);
        }

        $normalized = [
            'id' => $id,
            'name' => $name,
            'type' => $type,
            'required' => !empty($property['required']),
        ];
        if (in_array($type, self::SELECT_TYPES, true)) {
            $options = self::normalizeOptions(isset($property['options']) ? $property['options'] : [], $name);
            if (empty($options['ok'])) {
                return $options;
            }
            $normalized['options'] = $options['options'];
        }

        return ['ok' => true, 'property' => $normalized];
    }

    /**
     * @param mixed $options
     * @return array<string, mixed>
     */
    private static function normalizeOptions($options, $propertyName)
    {
        if (!is_array($options)) {
            return self::fail('Options for "' . $propertyName . '" are not valid.');
        }
        if (count($options) > self::MAX_OPTIONS) {
            return self::fail('"' . $propertyName . '" has too many options.');
        }
        $normalized = [];
        $seen = [];
        foreach ($options as $option) {
            if (!is_array($option)) {
                return self::fail('An option on "' . $propertyName . '" is not valid.');
            }
            $id = isset($option['id']) ? (string) $option['id'] : '';
            if (!preg_match('/^opt_[a-z0-9]{8,64}$/', $id) || isset($seen[$id])) {
                return self::fail('An option identifier on "' . $propertyName . '" is missing or duplicated.');
            }
            $label = trim((string) (isset($option['label']) ? $option['label'] : ''));
            if ($label === '' || strlen($label) > 80) {
                return self::fail('Each option on "' . $propertyName . '" needs a label.', $id);
            }
            $color = isset($option['color']) ? (string) $option['color'] : 'gray';
            if (!in_array($color, self::COLORS, true)) {
                return self::fail('Choose a supported color for "' . $label . '".', $id);
            }
            $seen[$id] = true;
            $normalized[] = [
                'id' => $id,
                'label' => $label,
                'color' => $color,
            ];
        }

        return ['ok' => true, 'options' => $normalized];
    }

    /**
     * @param array<string, mixed> $property
     */
    private static function optionExists(array $property, $optionId)
    {
        foreach ($property['options'] as $option) {
            if ($option['id'] === $optionId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed $value
     */
    private static function validDate($value)
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $date = \DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    /**
     * @param mixed $value
     */
    private static function validUrl($value)
    {
        if (!is_string($value) || preg_match('/[\s\r\n]/', $value) || strlen($value) > 2000) {
            return false;
        }
        if (!preg_match('#^https?://#i', $value)) {
            return false;
        }
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private static function emptyValue($type)
    {
        if ($type === 'checkbox') {
            return false;
        }
        if ($type === 'multi_select') {
            return [];
        }
        return null;
    }

    /**
     * @param mixed $ids
     * @return array<int, string>
     */
    private static function idList($ids)
    {
        if (!is_array($ids)) {
            return [];
        }
        $list = [];
        foreach ($ids as $id) {
            if (is_string($id)) {
                $list[] = $id;
            }
        }
        return $list;
    }

    private static function successMessage($mode)
    {
        if ($mode === 'complete') {
            return 'Evaluation completed.';
        }
        if ($mode === 'correction') {
            return 'Changes saved.';
        }
        return 'Draft saved.';
    }

    /**
     * @param \DOMNode $node
     * @param array<string, bool> $allowed
     * @param array<string, bool> $blocked
     */
    private static function sanitizeNode(\DOMNode $node, array $allowed, array $blocked, $isRoot = false)
    {
        if ($node->nodeType === XML_COMMENT_NODE && $node->parentNode) {
            $node->parentNode->removeChild($node);
            return;
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            self::sanitizeNode($child, $allowed, $blocked, false);
        }

        if ($isRoot) {
            if ($node instanceof \DOMElement) {
                self::stripAttributes($node);
            }
            return;
        }

        if ($node->nodeType !== XML_ELEMENT_NODE || !$node->parentNode) {
            return;
        }

        $name = strtolower($node->nodeName);
        if (isset($blocked[$name])) {
            $node->parentNode->removeChild($node);
            return;
        }
        if (!isset($allowed[$name])) {
            $parent = $node->parentNode;
            while ($node->firstChild) {
                $parent->insertBefore($node->firstChild, $node);
            }
            $parent->removeChild($node);
            return;
        }
        self::stripAttributes($node);
    }

    /**
     * @param \DOMElement $node
     */
    private static function stripAttributes(\DOMElement $node)
    {
        if (!$node->hasAttributes()) {
            return;
        }
        $names = [];
        foreach ($node->attributes as $attribute) {
            $names[] = $attribute->nodeName;
        }
        foreach ($names as $attribute) {
            $node->removeAttribute($attribute);
        }
    }

    /**
     * @param mixed $value
     */
    private static function notesAreBlank($value)
    {
        if (!is_string($value)) {
            return true;
        }
        return trim(self::plainText($value)) === '';
    }

    /**
     * @param mixed $value
     */
    private static function plainText($value)
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        return $text;
    }

    /**
     * @return array{ok: bool, conflict: bool, errors: array<int, array<string, mixed>>, schema: array<string, mixed>}
     */
    private static function fail($message, $propertyId = null)
    {
        return [
            'ok' => false,
            'conflict' => false,
            'schema' => ['properties' => []],
            'errors' => [[
                'property_id' => $propertyId,
                'message' => $message,
            ]],
        ];
    }
}
