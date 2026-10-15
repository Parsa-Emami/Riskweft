# RiskWeft product requirements v0

Status: planning; application source not implemented. English-facing app/docs are the release default. See Persian assessment for commercial hypotheses.

Target user: توسعه‌دهنده، تیم کوچک AppSec و مشاور معماری

Job to complete: مدل تهدید قابل‌حمل با ارزیابی deterministic و diff معنایی

Differentiation hypothesis: مدل تهدید به صورت فایل داخل repository؛ توضیح اینکه تغییر یک flow چه تهدیدی اضافه کرد

## MVP acceptance scope

- PWA روی دامنه با پردازش داخل مرورگر
- component/flow/boundary editor و نمای جدولی
- STRIDE قواعد نسخه‌دار با توضیح
- JSON import/export، revision diff و گزارش HTML

## Excluded until another decision

- اشتراک real-time و CRDT
- همگام‌سازی GitHub با OAuth
- LLM پیشنهاددهنده
- حساب سازمانی و billing

## End-to-end outcomes

1. مدل API با یک trust boundary بساز و تهدید را همراه rule ID و facts ببین
2. همان فایل و rule pack را دوباره ارزیابی کن؛ hash و findings برابر باشند
3. JSON export را در مرورگر جدید وارد کن؛ داده، review و diff حفظ شوند

Functional requirements F01–F20 and nonfunctional/release requirements are mapped one-to-one in `plans/requirements.json`. Tests belong with their feature commit, not postponed until launch. Success: a clean installed selected profile performs the core task and recovers from documented failures. Public demand remains unvalidated until real trials are recorded.

## UI flow

Select/import scoped input -> validate limits and permission -> show actual processing state -> explain result and confidence -> review/triage where appropriate -> export -> recover in clean environment. Loading, empty, stale, partial, unauthorized, rejected input, timeout/cancel and permanent failure are explicit. Provide table/keyboard alternatives to canvas, charts and graph paths. No sensitive content in page telemetry.
