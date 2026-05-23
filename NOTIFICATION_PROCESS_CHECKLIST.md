# Notification Parsing Checklist

## Progress
- [x] Normalize notification text before parsing
- [x] Ignore non-spending notifications
- [x] Try Llama first for spending notifications
- [x] Fall back to regex if Llama fails
- [x] Stop saving when both Llama and regex fail
- [x] Store transactions with `category_id`
- [x] Resolve `category_id` to category name in the dashboard
- [x] Keep `raw_text` and `dedupe_hash`

## Verification
- [ ] Run Dart analysis / formatter on mobile files
- [ ] Validate Laravel syntax for updated backend files
- [ ] Confirm dashboard `recent_transactions` shows category names
- [ ] Test one spending sample end to end
- [ ] Test one ignored income sample end to end
- [ ] Confirm duplicate notifications are skipped

## Done
- [ ] Mark all verification items complete