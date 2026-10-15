# RiskWeft acceptance gate

All scenarios are unexecuted in v0. Use synthetic or explicitly authorized owned data.

1. مدل API با یک trust boundary بساز و تهدید را همراه rule ID و facts ببین
2. همان فایل و rule pack را دوباره ارزیابی کن؛ hash و findings برابر باشند
3. JSON export را در مرورگر جدید وارد کن؛ داده، review و diff حفظ شوند

Also require negative input/auth tests, crash or interrupted operation, clean install/upgrade, export/restore and workload bounds. Claims must match selected private/public distribution profile.
