#!/usr/bin/env python3
"""Static guard for the Civil ID upload/removal hardening slice.

This repository's PHP/Docker test stack is not always available in lightweight
CI or agent runners. Keep this guard focused on the safety invariants this PR
must preserve so reviewers can quickly spot accidental regressions.
"""

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


def read(path: str) -> str:
    return (ROOT / path).read_text(encoding="utf-8")


def require(name: str, condition: bool) -> None:
    if not condition:
        raise AssertionError(f"Missing Civil ID hardening invariant: {name}")
    print(f"ok - {name}")


def main() -> None:
    account_controller = read("candidate/modules/v1/controllers/AccountController.php")
    candidate_model = read("common/models/Candidate.php")
    s3_manager = read("common/components/S3ResourceManager.php")
    account_cest = read("candidate/tests/functional/AccountCest.php")

    require(
        "back upload returns an API error when updateCivilId('back') fails",
        "if (!$model->updateCivilId('back'))" in account_controller
        and "'message' => $model->getErrors()" in account_controller,
    )
    require(
        "front upload returns an API error when updateCivilId('front') fails",
        "if (!$model->updateCivilId('front'))" in account_controller
        and "'message' => $model->getErrors()" in account_controller,
    )
    require(
        "Civil ID mutations consistently mark the candidate for verification",
        account_controller.count("candidate_civil_need_verification = true") >= 5
        and '"candidate_civil_need_verification"' in candidate_model,
    )
    require(
        "Civil ID delete targets the permanent photos prefix",
        '"photos/" . $this->oldAttributes[\'candidate_civil_photo_front\']' in candidate_model
        and '"photos/" . $this->oldAttributes[\'candidate_civil_photo_back\']' in candidate_model
        and '"candidate-civil-id/" . $this->oldAttributes' not in candidate_model,
    )
    require(
        "missing existing Civil ID files are treated as already deleted",
        "Civil ID file was already missing during delete." in candidate_model
        and "fileExistsInBucket($file)" in candidate_model,
    )
    require(
        "new Civil ID copy is verified before deleting the old file",
        "Civil ID copy destination was not found after copy." in candidate_model
        and "fileExistsInBucket($targetPath)" in candidate_model
        and candidate_model.index("fileExistsInBucket($targetPath)")
        < candidate_model.index("deleteFile('civil-id', $side)"),
    )
    require(
        "S3 manager exposes bucket-local headObject existence checks",
        "function fileExistsInBucket($name)" in s3_manager
        and "'Key' => $name" in s3_manager
        and "getAwsErrorCode() === 'NotFound'" in s3_manager,
    )
    require(
        "functional coverage asserts the Civil ID metadata update succeeds",
        "seeResponseContainsJson(['operation' => 'success'])" in account_cest,
    )


if __name__ == "__main__":
    main()
