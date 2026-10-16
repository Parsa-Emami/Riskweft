# RiskWeft: reference workflow

Status: planned domain acceptance; the current JSON structure preview does not implement this workflow.

Open synthetic model -> add boundary -> evaluate -> inspect explained threat -> edit flow -> semantic diff -> export -> import on clean browser.

Required invariants:

- Canvas is a projection
- Stable IDs and semantic normalization
- Threats retain matched facts and revision provenance

Required UAT:

- مدل API با یک trust boundary بساز و تهدید را همراه rule ID و facts ببین
- همان فایل و rule pack را دوباره ارزیابی کن؛ hash و findings برابر باشند
- JSON export را در مرورگر جدید وارد کن؛ داده، review و diff حفظ شوند

Machine contract: tests/scenarios/clone-to-value.json. C01/C04 create and freeze the actual synthetic positive/negative fixture formats; no real secrets, user source/traffic/case data or unlicensed corpus. A clean source clone must reproduce the entire workflow with restart and restore at C30.
