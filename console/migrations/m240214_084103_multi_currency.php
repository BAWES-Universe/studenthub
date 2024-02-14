<?php

use yii\db\Migration;

/**
 * Class m240214_084103_multi_currency
 */
class m240214_084103_multi_currency extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%currency}}', [
            'currency_id' => $this->primaryKey(),
            'title' => $this->string()->notNull(),
            'code' => $this->string()->notNull(),
            "currency_symbol" => $this->string(),
            "rate" => $this->double(),
            "decimal_place" => $this->tinyInteger(1),
            "sort_order" => $this->smallInteger(3),
            "status" => $this->boolean(),
            "datetime" => $this->dateTime()
        ], $tableOptions);

        $sql = "INSERT INTO `currency` (`currency_id`, `title`, `code`, `currency_symbol`, `rate`, `decimal_place`, `sort_order`, `status`, `datetime`) VALUES
(1, 'Saudi riyal ', 'SAR', ' ر.س', 3.749994, 2, 0, 1, '2024-01-30 16:12:40'),
(2, 'Kuwaiti dinar ', 'KWD', ' د.ك', 0.307679, 3, 0, 1, '2024-01-30 16:12:40'),
(3, 'Bahraini dinar ', 'BHD', ' د.ب', 0.376953, 3, 0, 1, '2024-01-30 16:12:40'),
(4, 'United Arab Emirates dirham ', 'AED', ' د.إ', 3.672925, 2, 0, 0, '2024-01-30 16:12:40'),
(5, 'Afghan afghani ', 'AFN', ' ؋', 73.328534, 2, 0, 0, '2024-01-30 16:12:40'),
(6, 'Albanian lek ', 'ALL', ' Lek', 95.939086, 2, 0, 0, '2024-01-30 16:12:40'),
(7, 'Armenian dram ', 'AMD', ' ֏', 405.076142, 2, 0, 0, '2024-01-30 16:12:40'),
(8, 'Netherlands Antillean guilder ', 'ANG', ' ƒ', 1.801358, 2, 0, 0, '2024-01-30 16:12:40'),
(9, 'Angolan kwanza ', 'AOA', ' Kz', 833.497117, 2, 0, 0, '2024-01-30 16:12:40'),
(10, 'Argentine peso ', 'ARS', ' N$', 825.269079, 2, 0, 0, '2024-01-30 16:12:40'),
(11, 'Australian dollar ', 'AUD', ' AU$', 1.51436, 2, 0, 0, '2024-01-30 16:12:40'),
(12, 'Aruban florin ', 'AWG', ' ƒ', 1.80125, 2, 0, 0, '2024-01-30 16:12:40'),
(13, 'Azerbaijani manat ', 'AZN', ' ₼', 1.705413, 2, 0, 0, '2024-01-30 16:12:40'),
(14, 'Bosnia and Herzegovina convertible mark ', 'BAM', ' KM', 1.805076, 2, 0, 0, '2024-01-30 16:12:40'),
(15, 'Barbadian dollar ', 'BBD', ' Bds$', 2.016871, 2, 0, 0, '2024-01-30 16:12:40'),
(16, 'Bangladeshi taka ', 'BDT', ' ৳', 109.626211, 2, 0, 0, '2024-01-30 16:12:40'),
(17, 'Bulgarian lev ', 'BGN', ' лв', 1.806248, 2, 0, 0, '2024-01-30 16:12:40'),
(18, 'Burundian franc ', 'BIF', ' Fr', 2849.6802, 0, 0, 0, '2024-01-30 16:12:40'),
(19, 'Bermudian dollar ', 'BMD', ' BD$', 1, 2, 0, 0, '2024-01-30 16:12:40'),
(20, 'Brunei dollar ', 'BND', ' B$', 1.340483, 2, 0, 0, '2024-01-30 16:12:40'),
(21, 'Bolivian boliviano ', 'BOB', ' \$b', 6.90263, 2, 0, 0, '2024-01-30 16:12:40'),
(22, 'Brazilian real ', 'BRL', ' R$', 4.951904, 2, 0, 0, '2024-01-30 16:12:40'),
(23, 'Bahamian dollar ', 'BSD', ' B$', 0.998892, 2, 0, 0, '2024-01-30 16:12:40'),
(24, 'Bitcoin', 'BTC', '₿', 0.000023003885, 8, 0, 0, '2024-01-30 16:12:40'),
(25, 'Bhutanese ngultrum ', 'BTN', ' Nu.', 83.065067, 2, 0, 0, '2024-01-30 16:12:40'),
(26, 'Botswana pula ', 'BWP', ' P', 13.590217, 2, 0, 0, '2024-01-30 16:12:40'),
(27, 'Belarusian ruble ', 'BYN', ' Br', 3.171452, 0, 0, 0, '2024-01-30 16:12:40'),
(28, 'Belarusian ruble ', 'BYR', ' Br', 19600, 0, 0, 0, '2024-01-30 16:12:40'),
(29, 'Belize dollar ', 'BZD', ' BZ$', 2.013456, 2, 0, 0, '2024-01-30 16:12:40'),
(30, 'Canadian dollar ', 'CAD', ' C$', 1.34028, 2, 0, 0, '2024-01-30 16:12:40'),
(31, 'Congolese franc ', 'CDF', ' Fr', 2744.99983, 2, 0, 0, '2024-01-30 16:12:40'),
(32, 'Swiss franc ', 'CHF', ' CHF', 0.862645, 2, 0, 0, '2024-01-30 16:12:40'),
(33, 'Chilean Unit of Account ', 'CLF', ' UF', 0.033714, 4, 0, 0, '2024-01-30 16:12:40'),
(34, 'Chilean peso ', 'CLP', ' CLP$', 930.270007, 0, 0, 0, '2024-01-30 16:12:40'),
(35, 'Chinese yuan ', 'CNY', ' 元', 7.102706, 2, 0, 0, '2024-01-30 16:12:40'),
(36, 'Colombian peso ', 'COP', ' COL$', 3928.24, 2, 0, 0, '2024-01-30 16:12:40'),
(37, 'Costa Rican colón ', 'CRC', ' ₡', 510.472956, 2, 0, 0, '2024-01-30 16:12:40'),
(38, 'Cuban convertible peso ', 'CUC', ' CUC$', 1, 2, 0, 0, '2024-01-30 16:12:40'),
(39, 'Cuban peso ', 'CUP', ' \$MN', 26.5, 2, 0, 0, '2021-11-04 14:19:04'),
(40, 'Cape Verdean escudo ', 'CVE', ' Esc', 101.76742, 2, 0, 0, '2024-01-30 16:12:40'),
(41, 'Czech koruna ', 'CZK', ' Kč', 22.924605, 2, 0, 0, '2024-01-30 16:12:40'),
(42, 'Djiboutian franc ', 'DJF', ' Fr', 177.849562, 0, 0, 0, '2024-01-30 16:12:40'),
(43, 'Danish krone ', 'DKK', ' kr', 6.879402, 2, 0, 0, '2024-01-30 16:12:40'),
(44, 'Dominican peso ', 'DOP', ' RD$', 58.83683, 2, 0, 0, '2024-01-30 16:12:40'),
(45, 'Algerian dinar ', 'DZD', ' د.ج', 134.607056, 2, 0, 0, '2024-01-30 16:12:40'),
(46, 'Egyptian pound ', 'EGP', ' £', 30.899697, 2, 0, 1, '2024-01-30 16:12:40'),
(47, 'Eritrean nakfa ', 'ERN', ' Nfk', 15, 2, 0, 0, '2024-01-30 16:12:40'),
(48, 'Ethiopian birr ', 'ETB', ' Br', 56.087143, 2, 0, 0, '2024-01-30 16:12:40'),
(49, 'Euro ', 'EUR', ' €', 0.92293, 2, 0, 0, '2024-01-30 16:12:40'),
(50, 'Fijian dollar ', 'FJD', ' FJ$', 2.234702, 2, 0, 0, '2024-01-30 16:12:40'),
(51, 'Falkland Islands pound ', 'FKP', ' £', 0.788641, 2, 0, 0, '2024-01-30 16:12:40'),
(52, 'British pound ', 'GBP', ' £', 0.78865, 2, 0, 0, '2024-01-30 16:12:40'),
(53, 'Georgian lari ', 'GEL', ' ₾', 2.684969, 2, 0, 0, '2024-01-30 16:12:40'),
(54, 'Guernsey pound ', 'GGP', ' £', 0.788641, 2, 0, 0, '2024-01-30 16:12:40'),
(55, 'Ghanaian cedi ', 'GHS', ' ₵', 12.334102, 2, 0, 0, '2024-01-30 16:12:40'),
(56, 'Gibraltar pound ', 'GIP', ' £', 0.788641, 2, 0, 0, '2024-01-30 16:12:40'),
(57, 'Gambian dalasi ', 'GMD', ' D', 67.425037, 2, 0, 0, '2024-01-30 16:12:40'),
(58, 'Guinean franc ', 'GNF', ' Fr', 8587.06587, 0, 0, 0, '2024-01-30 16:12:40'),
(59, 'Guatemalan quetzal ', 'GTQ', ' Q', 7.810834, 2, 0, 0, '2024-01-30 16:12:40'),
(60, 'Guyanese dollar ', 'GYD', ' GY$', 209.146285, 2, 0, 0, '2024-01-30 16:12:40'),
(61, 'Hong Kong dollar ', 'HKD', ' HK$', 7.815969, 2, 0, 0, '2024-01-30 16:12:40'),
(62, 'Honduran lempira ', 'HNL', ' L', 24.635788, 2, 0, 0, '2024-01-30 16:12:40'),
(63, 'Croatian kuna ', 'HRK', ' kn', 6.88032, 2, 0, 0, '2024-01-30 16:12:40'),
(64, 'Haitian gourde ', 'HTG', ' G', 131.344083, 2, 0, 0, '2024-01-30 16:12:40'),
(65, 'Hungarian forint ', 'HUF', ' Ft', 359.130271, 2, 0, 0, '2024-01-30 16:12:40'),
(66, 'Indonesian rupiah ', 'IDR', ' Rp', 15799.55, 2, 0, 0, '2024-01-30 16:12:40'),
(67, 'Israeli new shekel ', 'ILS', ' ₪', 3.649701, 2, 0, 0, '2024-01-30 16:12:40'),
(68, 'Manx pound ', 'IMP', ' £', 0.788641, 2, 0, 0, '2024-01-30 16:12:40'),
(69, 'Indian rupee ', 'INR', ' ₹', 83.110166, 2, 0, 0, '2024-01-30 16:12:40'),
(70, 'Iraqi dinar ', 'IQD', ' ع.د', 1308.537148, 3, 0, 0, '2024-01-30 16:12:40'),
(71, 'Iranian rial ', 'IRR', ' ﷼', 42050.000279, 2, 0, 0, '2024-01-30 16:12:40'),
(72, 'Icelandic króna ', 'ISK', ' kr', 137.059961, 2, 0, 0, '2024-01-30 16:12:40'),
(73, 'Jersey pound ', 'JEP', ' £', 0.788641, 2, 0, 0, '2024-01-30 16:12:40'),
(74, 'Jamaican dollar ', 'JMD', ' J$', 155.329232, 2, 0, 0, '2024-01-30 16:12:40'),
(75, 'Jordanian dinar ', 'JOD', ' د.ا', 0.709397, 3, 0, 1, '2024-01-30 16:12:40'),
(76, 'Japanese yen ', 'JPY', ' ¥', 147.229742, 0, 0, 0, '2024-01-30 16:12:40'),
(77, 'Kenyan shilling ', 'KES', ' Sh', 161.806681, 2, 0, 0, '2024-01-30 16:12:40'),
(78, 'Kyrgyzstani som ', 'KGS', ' с', 89.320252, 2, 0, 0, '2024-01-30 16:12:40'),
(79, 'Cambodian riel ', 'KHR', ' ៛', 4076.641224, 2, 0, 0, '2024-01-30 16:12:40'),
(80, 'Comorian franc ', 'KMF', ' Fr', 455.503806, 0, 0, 0, '2024-01-30 16:12:40'),
(81, 'North Korean won ', 'KPW', ' ₩', 899.991501, 2, 0, 0, '2024-01-30 16:12:40'),
(82, 'South Korean won ', 'KRW', ' ₩', 1329.355021, 0, 0, 0, '2024-01-30 16:12:40'),
(83, 'Cayman Islands dollar ', 'KYD', ' CI$', 0.832395, 2, 0, 0, '2024-01-30 16:12:40'),
(84, 'Kazakhstani tenge ', 'KZT', ' ₸', 449.146285, 2, 0, 0, '2024-01-30 16:12:40'),
(85, 'Lao kip ', 'LAK', ' ₭', 20699.393637, 2, 0, 0, '2024-01-30 16:12:40'),
(86, 'Lebanese pound ', 'LBP', ' ل.ل', 15013.243994, 2, 0, 1, '2024-01-30 16:12:40'),
(87, 'Sri Lankan rupee ', 'LKR', ' Rs', 317.662043, 2, 0, 0, '2024-01-30 16:12:40'),
(88, 'Liberian dollar ', 'LRD', ' LD$', 189.801804, 2, 0, 0, '2024-01-30 16:12:40'),
(89, 'Lesotho loti ', 'LSL', ' L', 18.810101, 2, 0, 0, '2024-01-30 16:12:40'),
(90, 'Lithuania Litas ', 'LTL', ' Lt', 2.95274, 2, 0, 0, '2021-11-04 14:19:04'),
(91, 'Latvia Lat ', 'LVL', ' ‎Ls', 0.60489, 2, 0, 0, '2021-11-04 14:19:04'),
(92, 'Libyan dinar ', 'LYD', ' ل.د', 4.818643, 3, 0, 0, '2024-01-30 16:12:40'),
(93, 'Moroccan dirham ', 'MAD', ' د.م.', 10.010983, 2, 0, 0, '2024-01-30 16:12:40'),
(94, 'Moldovan leu ', 'MDL', ' L', 17.724802, 2, 0, 0, '2024-01-30 16:12:40'),
(95, 'Malagasy ariary ', 'MGA', ' Ar', 4513.151823, 2, 0, 0, '2024-01-30 16:12:40'),
(96, 'Macedonian denar ', 'MKD', ' ден', 56.866636, 2, 0, 0, '2024-01-30 16:12:40'),
(97, 'Burmese kyat ', 'MMK', ' Ks', 2097.646516, 2, 0, 0, '2024-01-30 16:12:40'),
(98, 'Mongolian tögrög ', 'MNT', ' ₮', 3422.167028, 2, 0, 0, '2024-01-30 16:12:40'),
(99, 'Macanese pataca ', 'MOP', ' P', 8.037194, 2, 0, 0, '2024-01-30 16:12:40'),
(100, 'Mauritanian ouguiya ', 'MRO', ' UM', 356.999828, 2, 0, 0, '2021-11-04 14:19:04'),
(101, 'Mauritian rupee ', 'MUR', ' ₨', 44.851994, 2, 0, 0, '2024-01-30 16:12:40'),
(102, 'Maldivian rufiyaa ', 'MVR', ' .ރ', 15.384438, 2, 0, 0, '2024-01-30 16:12:40'),
(103, 'Malawian kwacha ', 'MWK', ' MK', 1683.000226, 2, 0, 0, '2024-01-30 16:12:40'),
(104, 'Mexican peso ', 'MXN', ' Mex$', 17.19195, 2, 0, 0, '2024-01-30 16:12:40'),
(105, 'Malaysian ringgit ', 'MYR', ' RM', 4.727501, 2, 0, 0, '2024-01-30 16:12:40'),
(106, 'Mozambican metical ', 'MZN', ' MT', 63.500647, 2, 0, 0, '2024-01-30 16:12:40'),
(107, 'Namibian dollar ', 'NAD', ' N$', 18.810154, 2, 0, 0, '2024-01-30 16:12:40'),
(108, 'Nigerian naira ', 'NGN', ' ₦', 893.499098, 2, 0, 0, '2024-01-30 16:12:40'),
(109, 'Nicaraguan córdoba ', 'NIO', ' C', 36.585478, 2, 0, 0, '2024-01-30 16:12:40'),
(110, 'Norwegian krone ', 'NOK', ' kr', 10.42896, 2, 0, 0, '2024-01-30 16:12:40'),
(111, 'Nepalese rupee ', 'NPR', ' ₨', 132.903863, 2, 0, 0, '2024-01-30 16:12:40'),
(112, 'New Zealand dollar ', 'NZD', ' NZ$', 1.63066, 2, 0, 0, '2024-01-30 16:12:40'),
(113, 'Omani rial ', 'OMR', ' ﷼', 0.384962, 3, 0, 1, '2024-01-30 16:12:40'),
(114, 'Panamanian balboa ', 'PAB', ' B/.', 0.998892, 2, 0, 0, '2024-01-30 16:12:40'),
(115, 'Peruvian sol ', 'PEN', ' S/.', 3.791878, 2, 0, 0, '2024-01-30 16:12:40'),
(116, 'Papua New Guinean kina ', 'PGK', ' K', 3.741647, 2, 0, 0, '2024-01-30 16:12:40'),
(117, 'Philippine piso ', 'PHP', ' ₱', 56.336498, 2, 0, 0, '2024-01-30 16:12:40'),
(118, 'Pakistani rupee ', 'PKR', ' ₨', 275.694416, 2, 0, 0, '2024-01-30 16:12:40'),
(119, 'Polish złoty ', 'PLN', ' zł', 4.028099, 2, 0, 0, '2024-01-30 16:12:40'),
(120, 'Paraguayan guaraní ', 'PYG', ' ₲', 7284.725427, 0, 0, 0, '2024-01-30 16:12:40'),
(121, 'Qatari riyal ', 'QAR', ' ﷼', 3.640499, 2, 0, 1, '2024-01-30 16:12:40'),
(122, 'Romanian leu ', 'RON', ' lei', 4.593605, 2, 0, 0, '2024-01-30 16:12:40'),
(123, 'Serbian dinar ', 'RSD', ' дин.', 108.149017, 2, 0, 0, '2024-01-30 16:12:40'),
(124, 'Russian ruble ', 'RUB', ' ₽', 89.28498, 2, 0, 0, '2024-01-30 16:12:40'),
(125, 'Rwandan franc ', 'RWF', ' Fr', 1268.568211, 0, 0, 0, '2024-01-30 16:12:40'),
(126, 'Solomon Islands dollar ', 'SBD', ' SI$', 8.418851, 2, 0, 0, '2024-01-30 16:12:40'),
(127, 'Seychellois rupee ', 'SCR', ' ₨', 13.154313, 2, 0, 0, '2024-01-30 16:12:40'),
(128, 'Sudanese pound ', 'SDG', ' ‫ج.س.‬', 601.000454, 2, 0, 0, '2024-01-30 16:12:40'),
(129, 'Swedish krona ', 'SEK', ' kr', 10.40299, 2, 0, 0, '2024-01-30 16:12:40'),
(130, 'Singapore dollar ', 'SGD', ' S$', 1.339085, 2, 0, 0, '2024-01-30 16:12:40'),
(131, 'Saint Helena pound ', 'SHP', ' £', 1.271203, 2, 0, 0, '2024-01-30 16:12:40'),
(132, 'Sierra Leonean leone ', 'SLL', ' Le', 19750.000422, 2, 0, 0, '2024-01-30 16:12:40'),
(133, 'Somali shilling ', 'SOS', ' Sh', 571.000078, 2, 0, 0, '2024-01-30 16:12:40'),
(134, 'Surinamese dollar ', 'SRD', ' Sr$', 36.769498, 2, 0, 0, '2024-01-30 16:12:40'),
(135, 'South Sudanese pound ', 'STD', ' £', 20697.981008, 2, 0, 0, '2021-11-04 14:19:04'),
(136, 'São Tomé and Príncipe dobra ', 'SVC', ' Db', 8.737655, 2, 0, 0, '2021-11-04 14:19:04'),
(137, 'Syrian pound ', 'SYP', ' £', 13001.848875, 2, 0, 0, '2024-01-30 16:12:40'),
(138, 'Swazi lilangeni ', 'SZL', ' L', 18.750346, 2, 0, 0, '2024-01-30 16:12:40'),
(139, 'Thai baht ', 'THB', ' ฿', 35.354495, 2, 0, 0, '2024-01-30 16:12:40'),
(140, 'Tajikistani somoni ', 'TJS', ' ЅМ', 10.89294, 2, 0, 0, '2024-01-30 16:12:40'),
(141, 'Turkmenistan manat ', 'TMT', ' m', 3.51, 2, 0, 0, '2021-11-04 14:19:04'),
(142, 'Tunisian dinar ', 'TND', ' د.ت', 3.121004, 3, 0, 0, '2024-01-30 16:12:40'),
(143, 'Tongan paʻanga ', 'TOP', ' T', 2.36535, 2, 0, 0, '2024-01-30 16:12:40'),
(144, 'Turkish lira ', 'TRY', ' ₺', 30.36143, 2, 0, 0, '2024-01-30 16:12:40'),
(145, 'Trinidad and Tobago dollar ', 'TTD', ' TT$', 6.759575, 2, 0, 0, '2024-01-30 16:12:40'),
(146, 'New Taiwan dollar ', 'TWD', ' NT$', 31.121504, 2, 0, 0, '2024-01-30 16:12:40'),
(147, 'Tanzanian shilling ', 'TZS', ' Sh', 2519.999747, 2, 0, 0, '2024-01-30 16:12:40'),
(148, 'Ukrainian hryvnia ', 'UAH', ' ₴', 37.852433, 2, 0, 0, '2024-01-30 16:12:40'),
(149, 'Ugandan shilling ', 'UGX', ' Sh', 3810.798339, 0, 0, 0, '2024-01-30 16:12:40'),
(150, 'United States dollar ', 'USD', ' US$', 1, 2, 0, 0, '2021-11-04 14:19:04'),
(151, 'Uruguayan peso ', 'UYU', ' $U', 38.929395, 2, 0, 0, '2024-01-30 16:12:40'),
(152, 'Uzbekistani soʻm ', 'UZS', ' сўм', 12331.333641, 2, 0, 0, '2024-01-30 16:12:40'),
(153, 'Venezuelan bolívar soberano ', 'VEF', ' Bs.', 3611544.901313, 2, 0, 0, '2024-01-30 16:12:40'),
(154, 'Vietnamese đồng ', 'VND', ' ₫', 24410, 0, 0, 0, '2024-01-30 16:12:40'),
(155, 'Vanuatu vatu ', 'VUV', ' Vt', 119.855926, 0, 0, 0, '2024-01-30 16:12:40'),
(156, 'Samoan tālā ', 'WST', ' T', 2.747917, 2, 0, 0, '2024-01-30 16:12:40'),
(157, 'Central African CFA franc ', 'XAF', ' Fr', 605.402836, 0, 0, 0, '2024-01-30 16:12:40'),
(158, 'Silver Ounce ', 'XAG', ' oz', 0.043255, 0, 0, 0, '2024-01-30 16:12:40'),
(159, 'Gold Ounce ', 'XAU', ' oz', 0.000491, 0, 0, 0, '2024-01-30 16:12:40'),
(160, 'Eastern Caribbean dollar ', 'XCD', ' EC$', 2.70255, 2, 0, 0, '2021-11-04 14:19:04'),
(161, 'IMF Special Drawing Rights ', 'XDR', ' SDR', 0.750069, 0, 0, 0, '2024-01-30 16:12:40'),
(162, 'West African CFA franc ', 'XOF', ' Fr', 605.40563, 0, 0, 0, '2024-01-30 16:12:40'),
(163, 'CFP franc ', 'XPF', ' Fr', 110.550191, 0, 0, 0, '2024-01-30 16:12:40'),
(164, 'Yemeni rial ', 'YER', ' ﷼', 250.433829, 2, 0, 0, '2024-01-30 16:12:40'),
(165, 'South African rand ', 'ZAR', ' R', 18.86557, 2, 0, 0, '2024-01-30 16:12:40'),
(166, 'Zambian Kwacha ', 'ZMK', ' ZK', 9001.204105, 2, 0, 0, '2024-01-30 16:12:40'),
(167, 'Zambian kwacha ', 'ZMW', ' ZK', 26.994675, 2, 0, 0, '2024-01-30 16:12:40'),
(168, 'Zimbabwean dollar ', 'ZWL', ' Z$', 321.999592, 2, 0, 0, '2021-11-04 14:19:04'),
(169, 'MRU', 'MRU', NULL, 39.624997, 2, 0, 1, '2024-01-30 16:12:40'),
(170, 'SLE', 'SLE', NULL, 22.869271, 2, 0, 1, '2024-01-30 16:12:40'),
(171, 'VES', 'VES', NULL, 36.156775, 2, 0, 1, '2024-01-30 16:12:40');";

        Yii::$app->db->createCommand($sql)->execute();

        // company

        $this->addColumn("company", "country_id", $this->integer(11));

        $this->createIndex('ind-company-country_id', 'company', 'country_id');

        $this->addForeignKey(
            'fk-company-country_id', 'company', 'country_id', 'country', 'country_id'
        );

        $this->addColumn("company", "currency_code", $this->char(3)->defaultValue("KWD"));

        $this->createIndex('ind-company-currency_code', 'company', 'currency_code');

        //transfer

        $this->addColumn("transfer", "currency_code", $this->char(3)->defaultValue("KWD"));

        $this->createIndex('ind-transfer-currency_code', 'transfer', 'currency_code');

        //company_request

        $this->addColumn("company_request", "currency_code", $this->char(3)->defaultValue("KWD"));
        $this->addColumn("company_request", "country_id", $this->integer(11));

        $this->createIndex('ind-company_request-country_id', 'company_request', 'country_id');

        $this->createIndex('ind-company_request-currency_code', 'company_request', 'currency_code');

        $this->addForeignKey(
            'fk-company_request-country_id', 'company_request', 'country_id', 'country', 'country_id'
        );

        //fulltimer
        $this->addColumn("fulltimer", "currency_code", $this->char(3)->defaultValue("KWD"));

        //candidate
        $this->addColumn("candidate", "currency_code", $this->char(3)->defaultValue("KWD"));

        //transfer file
        $this->addColumn("transfer_file", "currency_code", $this->char(3)->defaultValue("KWD"));

        //transfer candidate
        $this->addColumn("transfer_candidate", "currency_code", $this->char(3)->defaultValue("KWD"));

        // country

        $this->addColumn("country", "iso", $this->char(3));
        $this->addColumn("country", "emoji", $this->string());
        $this->addColumn("country", "country_code", $this->smallInteger(3));

        // feed data in country with iso

        //$this->db->createCommand("ALTER TABLE country MODIFY emoji VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
        //    ->execute();

        $countries = [
            [1, 'Afghanistan', 'أفغانستان', 'AF', '🇦🇫', 93],
            [2, 'Albania', 'ألبانيا', 'AL', '🇦🇱', 355],
            [3, 'Algeria', 'الجزائر', 'DZ', '🇩🇿', 213],
            [4, 'Andorra', 'أندورا', 'AD', '🇦🇩', 376],
            [5, 'Angola', 'أنغولا', 'AO', '🇦🇴', 244],
            [6, 'Argentina', 'الأرجنتين', 'AR', '🇦🇷', 54],
            [7, 'Armenia', 'أرمينيا', 'AM', '🇦🇲', 374],
            [8, 'Australia', 'أستراليا', 'AU', '🇦🇺', 61],
            [9, 'Austria', 'النمسا', 'AT', '🇦🇹', 43],
            [10, 'Azerbaijan', 'أذربيجان', 'AZ', '🇦🇿', 994],
            [11, 'Bahamas', 'جزر البهاما', 'BS', '🇧🇸', 1242],
            [12, 'Bahrain', 'البحرين', 'BH', '🇧🇭', 973],
            [13, 'Bangladesh', 'بنغلاديش', 'BD', '🇧🇩', 973],
            [14, 'Barbados', 'بربادوس', 'BB', '🇧🇧', 1246],
            [15, 'Belarus', 'روسيا البيضاء', 'BY', '🇧🇾', 375],
            [16, 'Belgium', 'بلجيكا', 'BE', '🇧🇪', 32],
            [17, 'Belize', 'بليز', 'BZ', '🇧🇿', 501],
            [18, 'Benin', 'بنين', 'BJ', '🇧🇯', 229],
            [19, 'Bhutan', 'بوتان', 'BT', '🇧🇹', 975],
            [20, 'Bolivia', 'بوليفيا', 'BO', '🇧🇴', 591],
            [21, 'Bosnia-Herzegovina', 'البوسنة والهرسك', 'BA', '🇧🇦', 387],
            [22, 'Botswana', 'بوتسوانا', 'BW', '🇧🇼', 267],
            [23, 'Brazil', 'البرازيل', 'BR', '🇧🇷', 55],
            [24, 'Britain', 'بريطانيا', 'GB', '🇬🇧', 44],
            [25, 'Brunei', 'بروناي', 'BN', '🇧🇳', 673],
            [26, 'Bulgaria', 'بلغاريا', 'BG', '🇧🇬', 359],
            [27, 'Burkina', 'وركينا فاسو', 'BF', '🇧🇫', 226],
            [28, 'Burma', 'بورما', 'MM', '🇲🇲', 95],
            [29, 'Burundi', 'بوروندي', 'BI', '🇧🇮', 257],
            [30, 'Cambodia', 'كمبوديا', 'KH', '🇰🇭', 855],
            [31, 'Cameroon', 'الكاميرون', 'CM', '🇨🇲', 237],
            [32, 'Canada', 'كندا', 'CA', '🇨🇦', 1],
            [33, 'Cape Verde Islands', 'جزر الرأس الأخضر', 'CV', '🇨🇻', 238],
            [34, 'Chad', 'تشاد', 'TD', '🇹🇩', 235],
            [35, 'Chile', 'تشيلي', 'CL', '🇨🇱', 56],
            [36, 'China', 'الصين', 'CN', '🇨🇳', 86],
            [37, 'Colombia', 'كولومبيا', 'CO', '🇨🇴', 57],
            [38, 'Congo', 'الكونغو', 'CG', '🇨🇬', 243],
            [39, 'Costa Rica', 'كوستا ريكا', 'CR', '🇨🇷', 506],
            [40, 'Croatia', 'كرواتيا', 'HR', '🇭🇷', 385],
            [41, 'Cuba', 'كوبا', 'CU', '🇨🇺', 53],
            [42, 'Cyprus', 'قبرص', 'CY', '🇨🇾', 357],
            [43, 'Czech Republic', 'جمهورية التشيك', 'CZ', '🇨🇿', 420],
            [44, 'Denmark', 'الدنمارك', 'DK', '🇩🇰', 45],
            [45, 'Djibouti', 'جيبوتي', 'DJ', '🇩🇯', 253],
            [46, 'Dominica', 'دومينيكا', 'DM', '🇩🇲', 767],
            [47, 'Dominican Republic', 'جمهورية الدومينيكان', 'DO', '🇩🇴', 809],
            [48, 'Ecuador', 'الإكوادور', 'EC', '🇪🇨', 593],
            [49, 'Egypt', 'مصر', 'EG', '🇪🇬', 20],
            [50, 'El Salvador', 'السلفادور', 'SV', '🇸🇻', 503],
            [52, 'Eritrea', 'إريتريا', 'ER', '🇪🇷', 291],
            [53, 'Estonia', 'استونيا', 'EE', '🇪🇪', 372],
            [54, 'Ethiopia', 'أثيوبيا', 'ET', '🇪🇹', 251],
            [55, 'Fiji', 'فيجي', 'FJ', '🇫🇯', 679],
            [56, 'Finland', 'فنلندا', 'FI', '🇫🇮', 358],
            [57, 'France', 'فرنسا', 'FR', '🇫🇷', 33],
            [58, 'Gabon', 'الغابون', 'GA', '🇬🇦', 241],
            [59, 'Gambia', 'غامبيا', 'GM', '🇬🇲', 220],
            [60, 'Georgia', 'جورجيا', 'GE', '🇬🇪', 995],
            [61, 'Germany', 'ألمانيا', 'DE', '🇩🇪', 49],
            [62, 'Ghana', 'غانا', 'GH', '🇬🇭', 233],
            [63, 'Greece', 'يونان', 'GR', '🇬🇷', 30],
            [64, 'Grenada', 'غرينادا', 'GD', '🇬🇩', 473],
            [65, 'Guatemala', 'غواتيمالا', 'GT', '🇬🇹', 502],
            [66, 'Guinea', 'غينيا', 'GN', '🇬🇳', 224],
            [67, 'Guyana', 'غيانا', 'GY', '🇬🇾', 592],
            [68, 'Haiti', 'هايتي', 'HT', '🇭🇹', 509],
            [70, 'Honduras', 'هندوراس', 'HN', '🇭🇳', 504],
            [71, 'Hungary', 'هنغاريا', 'HU', '🇭🇺', 36],
            [72, 'Iceland', 'أيسلندا', 'IS', '🇮🇸', 354],
            [73, 'India', 'الهند', 'IN', '🇮🇳', 91],
            [74, 'Indonesia', 'أندونيسيا', 'ID', '🇮🇩', 62],
            [75, 'Iran', 'إيران', 'IR', '🇮🇷', 98],
            [76, 'Iraq', 'العراق', 'IQ', '🇮🇶', 964],
            [77, 'Ireland', 'ايرلندا', 'IE', '🇮🇪', 353],
            [78, 'Italy', 'إيطاليا', 'IT', '🇮🇹', 39],
            [79, 'Jamaica', 'جامايكا', 'JM', '🇯🇲', 876],
            [80, 'Japan', 'اليابان', 'JP', '🇯🇵', 81],
            [81, 'Jordan', 'الأردن', 'JO', '🇯🇴', 962],
            [82, 'Kazakhstan', 'كازاخستان', 'KZ', '🇰🇿', 7],
            [83, 'Kenya', 'كينيا', 'KE', '🇰🇪', 254],
            [84, 'Kuwait', 'الكويت', 'KW', '🇰🇼', 965],
            [85, 'Laos', 'لاوس', 'LA', '🇱🇦', 853],
            [86, 'Latvia', 'لاتفيا', 'LV', '🇱🇻', 371],
            [87, 'Lebanon', 'لبنان', 'LB', '🇱🇧', 961],
            [88, 'Liberia', 'ليبيريا', 'LR', '🇱🇷', 231],
            [89, 'Libya', 'ليبيا', 'LY', '🇱🇾', 281],
            [90, 'Lithuania', 'ليتوانيا', 'LT', '🇱🇹', 370],
            [91, 'Macedonia', 'مقدونيا', 'MK', '🇲🇰', 389],
            [92, 'Madagascar', 'مدغشقر', 'MG', '🇲🇬', 261],
            [93, 'Malawi', 'ملاوي', 'MW', '🇲🇼', 265],
            [94, 'Malaysia', 'ماليزيا', 'MY', '🇲🇾', 60],
            [95, 'Maldives', 'جزر المالديف', 'MV', '🇲🇻', 960],
            [96, 'Mali', 'مالي', 'ML', '🇲🇱', 223],
            [97, 'Malta', 'مالطا', 'MT', '🇲🇹', 356],
            [98, 'Mauritania', 'موريتانيا', 'MR', '🇲🇷', 222],
            [99, 'Mauritius', 'موريشيوس', 'MU', '🇲🇺', 230],
            [100, 'Mexico', 'المكسيك', 'MX', '🇲🇽', 52],
            [101, 'Moldova', 'مولدوفا', 'MD', '🇲🇩', 373],
            [102, 'Monaco', 'موناكو', 'MC', '🇲🇨', 377],
            [103, 'Mongolia', 'منغوليا', 'MN', '🇲🇳', 976],
            [104, 'Montenegro', 'الجبل الأسود', 'ME', '🇲🇪', 382],
            [105, 'Morocco', 'بلاد المغرب', 'MA', '🇲🇦', 212],
            [106, 'Mozambique', 'موزمبيق', 'MZ', '🇲🇿', 258],
            [107, 'Namibia', 'ناميبيا', 'NA', '🇳🇦', 264],
            [108, 'Nepal', 'نيبال', 'NP', '🇳🇵', 977],
            [109, 'Netherlands', 'هولندا', 'nl', '🇳🇱', 31],
            [110, 'New Zealand', 'نيوزيلندا', 'NZ', '🇳🇿', 64],
            [111, 'Nicaragua', 'نيكاراغوا', 'NI', '🇳🇮', 505],
            [112, 'Niger', 'النيجر', 'NE', '🇳🇪', 227],
            [113, 'Nigeria', 'نيجيريا', 'NG', '🇳🇬', 234],
            [114, 'North Korea', 'كوريا الشمالية', 'KP', '🇰🇵', 850],
            [115, 'Norway', 'النرويج', 'NO', '🇳🇴', 47],
            [116, 'Oman', 'عمان', 'OM', '🇴🇲', 968],
            [117, 'Pakistan', 'باكستان', 'PK', '🇵🇰', 92],
            [118, 'Panama', 'بناما', 'PA', '🇵🇦', 507],
            [119, 'Papua New Guinea', 'بابوا غينيا الجديدة', 'PG', '🇵🇬', 675],
            [120, 'Paraguay', 'باراغواي', 'PY', '🇵🇾', 595],
            [121, 'Peru', 'بيرو', 'PE', '🇵🇪', 51],
            [122, 'the Philippines', 'الفلبين', 'PH', '🇵🇭', 63],
            [123, 'Poland', 'بولندا', 'PL', '🇵🇱', 48],
            [124, 'Portugal', 'البرتغال', 'PT', '🇵🇹', 351],
            [125, 'Qatar', 'دولة قطر', 'QA', '🇶🇦', 974],
            [126, 'Romania', 'رومانيا', 'RO', '🇷🇴', 40],
            [127, 'Russia', 'روسيا', 'RU', '🇷🇺', 7],
            [128, 'Rwanda', 'رواندا', 'RW', '🇷🇼', 250],
            [129, 'Saudi Arabia', 'السعودية', 'SA', '🇸🇦', 966],
            [131, 'Senegal', 'السنغال', 'SN', '🇸🇳', 221],
            [132, 'Serbia', 'صربيا', 'RS', '🇷🇸', 381],
            [133, 'Seychelles', 'سيشيل', 'SC', '🇸🇨', 248],
            [134, 'Sierra Leone', 'سيرا ليون', 'SL', '🇸🇱', 232],
            [135, 'Singapore', 'سنغافورة', 'SG', '🇸🇬', 65],
            [136, 'Slovakia', 'سلوفاكيا', 'SK', '🇸🇰', 421],
            [137, 'Slovenia', 'سلوفينيا', 'SI', '🇸🇮', 386],
            [138, 'Somalia', 'الصومال', 'SO', '🇸🇴', 252],
            [139, 'South Africa', 'جنوب أفريقيا', 'ZA', '🇿🇦', 27],
            [140, 'South Korea', 'كوريا الجنوبية', 'KR', '🇰🇷', 82],
            [141, 'Spain', 'إسبانيا', 'ES', '🇪🇸', 34],
            [142, 'Sri Lanka', 'سيريلانكا', 'LK', '🇱🇰', 94],
            [143, 'Sudan', 'سودان', 'SD', '🇸🇩', 249],
            [144, 'Suriname', 'سورينام', 'SR', '🇸🇷', 597],
            [145, 'Swaziland', 'سوازيلاند', 'SZ', '🇸🇿', 268],
            [146, 'Sweden', 'السويد', 'SE', '🇸🇪', 46],
            [147, 'Switzerland', 'سويسرا', 'xa', '🇨🇭', 41],
            [148, 'Syria', 'سوريا', 'SY', '🇸🇾', 963],
            [149, 'Taiwan', 'تايوان', 'TW', '🇹🇼', 886],
            [150, 'Tajikistan', 'طاجيكستان', 'TJ', '🇹🇯', 992],
            [151, 'Tanzania', 'تنزانيا', 'TZ', '🇹🇿', 255],
            [152, 'Thailand', 'تايلاند', 'TH', '🇹🇭', 66],
            [153, 'Togo', 'توغو', 'TG', '🇹🇬', 228],
            [154, 'Trinidad and Tobago', 'ترينداد وتوباغو', 'TT', '🇹🇹', 868],
            [155, 'Tobagonian', 'Tobagonian', 'tt', NULL, 868],
            [156, 'Tunisia', 'تونس', 'TN', '🇹🇳', 216],
            [157, 'Turkey', 'الديك الرومي', 'TR', '🇹🇷', 90],
            [158, 'Turkmenistan', 'تركمانستان', 'TM', '🇹🇲', 993],
            [159, 'Tuvalu', 'توفالو', 'TV', '🇹🇻', 688],
            [160, 'Uganda', 'أوغندا', 'UG', '🇺🇬', 256],
            [161, 'Ukraine', 'أوكرانيا', 'UA', '🇺🇦', 380],
            [162, 'United Arab Emirates', 'الإمارات العربية المتحدة', 'AE', '🇦🇪', 971],
            [163, 'United Kingdom', 'المملكة المتحدة', 'gb', '🇬🇧', 44],
            [164, 'United States of America', 'الولايات المتحدة الأمريكية', 'US', '🇺🇸', 1],
            [165, 'Uruguay', 'أوروغواي', 'UY', '🇺🇾', 598],
            [166, 'Uzbekistan', 'أوزبكستان', 'UZ', '🇺🇿', 998],
            [167, 'Vanuatu', 'فانواتو', 'VU', '🇻🇺', 678],
            [168, 'Venezuela', 'فنزويلا', 'VE', '🇻🇪', 58],
            [169, 'Vietnam', 'فيتنام', 'VN', '🇻🇳', 84],
            [170, 'Wales', 'ويلز', 'xw', NULL, 681],
            [171, 'Western Samoa', 'ساموا الغربية', 'WS', '🇼🇸', 685],
            [172, 'Yemen', 'يمني', 'YE', '🇾🇪', 967],
            [174, 'Zaire', 'زائير', 'dr', NULL, 243],
            [175, 'Zambia', 'زامبيا', 'ZM', '🇿🇲', 260],
            [176, 'Zimbabwe', 'زيمبابوي', 'ZW', '🇿🇼', 263]
        ];

        foreach ($countries as $country) {
            \common\models\Country::updateAll([
                "iso" => $country[3],
               // "emoji" =>  new \yii\db\Expression($country[4]),
                "country_code" => $country[5],
            ], [
                "country_name_en" => $country[1]
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240214_084103_multi_currency cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240214_084103_multi_currency cannot be reverted.\n";

        return false;
    }
    */
}
