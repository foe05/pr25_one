# Gotchas & Pitfalls

Things to watch out for in this codebase.

## [2026-01-12 10:55]
DOMPDF requires manual composer install step - the library cannot be included in git repository. Created INSTALL-DOMPDF.md with instructions. PDF service gracefully handles missing library with clear error messages.

_Context: Subtask 4.1 - PDF library integration. Composer and PHP commands are restricted in the build environment, so DOMPDF must be installed manually on the target server._
