# Bulk Operations & Import

Allow Vorstand to perform bulk operations: import historical data from Excel, bulk update records, mass assignment of meldegruppen, and batch deletion with safeguards.

## Rationale
Migration from Excel (pain-6-5) requires import capability. Managing large datasets one-by-one is impractical. Unlike competitors that lock users into their systems, easy import/export ensures data portability and reduces switching costs.

## User Stories
- As a Vorstand migrating from Excel, I want to import historical data so that we have complete records
- As an admin, I want bulk operations so that I can manage large datasets efficiently
- As a user switching from competitors, I want to import my existing data

## Acceptance Criteria
- [ ] CSV/Excel import with column mapping interface
- [ ] Preview and validation before import commit
- [ ] Bulk status update for multiple records
- [ ] Undo capability for bulk operations
- [ ] Import from state hunting association Excel templates (LJV formats)
