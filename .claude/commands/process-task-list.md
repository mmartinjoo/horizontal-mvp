# Claude Code: Task List Processor

## Overview
Guidelines for implementing and managing task lists to track PRD implementation progress.

## Implementation Protocol

### Core Rule: One Sub-Task at a Time
- **Implement ONE sub-task completely** before moving to the next
- **Ask user permission** before starting each new sub-task
- **Wait for "yes", "y", or "go"** confirmation
- **Never auto-proceed** to the next task

### Sub-Task Completion Sequence

When finishing a **sub-task**:

1. **Mark sub-task complete** - Change `[ ]` to `[x]` in task file
2. **Update task file** immediately
3. **Test the specific change** if applicable
4. **Ask user for permission** to proceed to next sub-task

Example:
```bash
# Update task file
sed -i 's/- \[ \] 1.1 Create interface/- [x] 1.1 Create interface/' ./tasks/tasks-prd-feature.md

# Show completion
echo "✓ Sub-task 1.1 completed. Ready for next sub-task? (y/n)"
```

### Parent Task Completion Sequence

When **ALL sub-tasks** under a parent task are `[x]`:

1. **Run full test suite first**:
   ```bash
   # Run appropriate test command for project
   npm test                    # Node.js projects
   # OR
   pytest                      # Python projects  
   # OR
   cargo test                  # Rust projects
   # OR
   bin/rails test             # Rails projects
   ```

2. **Only if ALL tests pass**, proceed with git operations:
   ```bash
   # Stage all changes
   git add .
   
   # Clean up any temporary files
   rm -f *.tmp temp_* debug_*
   
   # Remove any debug/console statements
   # (manual review of files)
   ```

3. **Commit with descriptive message**:
   ```bash
   git commit -m "feat: implement user authentication flow" \
              -m "- Add login/logout components" \
              -m "- Implement JWT token handling" \
              -m "- Add form validation and error states" \
              -m "- Include comprehensive unit tests" \
              -m "Completes Task 2.0 from PRD user-auth"
   ```

4. **Mark parent task complete** - Change parent `[ ]` to `[x]`

### Commit Message Format
Use conventional commit format with detailed multi-line messages:

```bash
git commit -m "[type]: [summary]" \
           -m "- [key change 1]" \
           -m "- [key change 2]" \
           -m "- [key change 3]" \
           -m "Related to Task [number] in PRD [name]"
```

**Commit Types:**
- `feat:` - New feature implementation
- `fix:` - Bug fixes
- `refactor:` - Code restructuring
- `test:` - Adding or updating tests
- `docs:` - Documentation updates
- `style:` - Code formatting changes

## Task File Maintenance

### Regular Updates
After each sub-task completion:

```bash
# Check current task status
grep -E "^\s*- \[[ x]\]" ./tasks/tasks-prd-[feature].md

# Update task file
# (mark completed tasks with [x])

# Add new tasks if discovered
echo "- [ ] X.X New discovered task" >> ./tasks/tasks-prd-[feature].md
```

### Relevant Files Section Maintenance
Keep the "Relevant Files" section current:

```markdown
## Relevant Files

- `src/components/NewComponent.tsx` - ✓ Created - Main feature component
- `src/components/NewComponent.test.tsx` - ✓ Created - Unit tests
- `src/types/feature.ts` - ✓ Modified - Added new interfaces
- `src/api/endpoints.ts` - 🔄 In Progress - Adding new routes
- `src/utils/helpers.ts` - ⏳ Pending - Utility functions needed
```

## Workflow Commands

### Check Current Status
```bash
# Show next incomplete sub-task
grep -A 5 -B 1 "- \[ \]" ./tasks/tasks-prd-[feature].md | head -10

# Count completed vs total tasks
total=$(grep -c "- \[" ./tasks/tasks-prd-[feature].md)
completed=$(grep -c "- \[x\]" ./tasks/tasks-prd-[feature].md)
echo "Progress: $completed/$total tasks completed"
```

### Pre-Implementation Check
```bash
# Ensure clean working directory
git status

# Verify all tests pass before starting
npm test

# Show current task to implement
echo "Next task to implement:"
grep -m 1 "- \[ \]" ./tasks/tasks-prd-[feature].md
```

### Post-Implementation Verification
```bash
# Run tests
npm test

# Check git status
git status

# Verify task file updated
git diff ./tasks/tasks-prd-[feature].md
```

## Error Handling

### If Tests Fail
```bash
# Do NOT commit if tests fail
echo "❌ Tests failed. Fix issues before proceeding."
npm test -- --verbose  # Get detailed test output

# Fix issues, then re-run tests
npm test

# Only proceed with git operations after all tests pass
```

### If Git Issues
```bash
# Check for conflicts or issues
git status

# If conflicts exist, resolve them before committing
git diff

# Ensure clean commit
git add .
git commit -m "fix: resolve merge conflicts in task implementation"
```

## Claude Code Integration

### Session Startup
```bash
# Navigate to project directory
cd /path/to/project

# Check task file exists
ls -la ./tasks/tasks-prd-*.md

# Show current progress
echo "Current implementation status:"
grep -E "^\s*- \[[ x]\]" ./tasks/tasks-prd-[feature].md
```

### Between Tasks
```bash
# Always ask before proceeding
echo "Sub-task completed. Continue to next sub-task? (y/n)"
# Wait for user input

# If user says yes, show next task
echo "Next sub-task:"
grep -m 1 "- \[ \]" ./tasks/tasks-prd-[feature].md
```

## Key Principles
1. **One task at a time** - Complete focus on current sub-task
2. **Test-driven progress** - Tests must pass before committing
3. **Clear documentation** - Update task files immediately
4. **User control** - Always ask permission before proceeding
5. **Clean commits** - Meaningful messages with full context