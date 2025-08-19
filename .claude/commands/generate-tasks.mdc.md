# Claude Code: Task List Generator

## Overview
Generates detailed, step-by-step task lists from existing PRDs to guide developers through feature implementation.

## Workflow

### Phase 1: PRD Analysis
When user requests task generation from a PRD:

1. **Read the specified PRD file** from `./tasks/prd-[feature-name].md`
2. **Analyze functional requirements** and user stories
3. **Identify implementation phases** and dependencies

### Phase 2: High-Level Task Generation
Create parent tasks based on PRD analysis:

1. **Generate 4-6 high-level tasks** that cover the full implementation
2. **Present tasks to user** in the specified format (without sub-tasks)
3. **Inform user**: "High-level tasks generated. Type 'Go' to generate detailed sub-tasks."
4. **Wait for confirmation** before proceeding

### Phase 3: Detailed Sub-Task Generation
After user confirms with "Go":

1. **Break down each parent task** into actionable sub-tasks
2. **Ensure logical progression** and clear dependencies
3. **Include testing considerations** for each component
4. **Identify relevant files** that will be created/modified

### Phase 4: File Structure Analysis
Based on tasks and PRD, identify:

- **Source files** to be created/modified
- **Test files** for each component
- **Configuration files** if needed
- **Documentation updates** required

## Output Format

Create `./tasks/tasks-[prd-filename].md` with this structure:

```markdown
# Tasks: [Feature Name]

## Relevant Files

- `src/components/FeatureComponent.tsx` - Main component for [feature]
- `src/components/FeatureComponent.test.tsx` - Unit tests for main component
- `src/api/feature-endpoints.ts` - API routes and handlers
- `src/api/feature-endpoints.test.ts` - API endpoint tests
- `src/types/feature.ts` - TypeScript type definitions
- `src/utils/feature-helpers.ts` - Utility functions
- `src/utils/feature-helpers.test.ts` - Utility function tests

### Testing Notes
- Run tests with: `npm test` or `yarn test`
- Run specific tests: `npm test FeatureComponent.test.tsx`
- Tests should be co-located with source files

## Tasks

- [ ] 1.0 Set up component structure and types
  - [ ] 1.1 Create TypeScript interface definitions
  - [ ] 1.2 Set up main component file with basic structure
  - [ ] 1.3 Create initial test file with setup

- [ ] 2.0 Implement core functionality
  - [ ] 2.1 Add state management and hooks
  - [ ] 2.2 Implement main user interactions
  - [ ] 2.3 Add form validation and error handling

- [ ] 3.0 Create API integration
  - [ ] 3.1 Set up API endpoint handlers
  - [ ] 3.2 Implement data fetching logic
  - [ ] 3.3 Add error handling and loading states

- [ ] 4.0 Add comprehensive testing
  - [ ] 4.1 Write unit tests for components
  - [ ] 4.2 Write integration tests for API calls
  - [ ] 4.3 Add end-to-end test scenarios

- [ ] 5.0 Polish and documentation
  - [ ] 5.1 Add accessibility features
  - [ ] 5.2 Update component documentation
  - [ ] 5.3 Perform final testing and cleanup
```

## Command Examples

```bash
# Read existing PRD
cat ./tasks/prd-[feature-name].md

# Create task list file
touch ./tasks/tasks-prd-[feature-name].md

# Verify file structure
ls -la ./tasks/

# Show task completion status
grep -E "^\s*- \[[ x]\]" ./tasks/tasks-prd-[feature-name].md
```

## Phase Control
The workflow explicitly requires:

1. **Generate parent tasks first**
2. **Wait for user "Go" confirmation**
3. **Then generate detailed sub-tasks**

This ensures the high-level approach aligns with user expectations before diving into implementation details.

## Key Principles
1. **Logical task progression** - Each task builds on previous work
2. **Testable components** - Every significant piece should have tests
3. **Clear file organization** - Maintain consistent project structure
4. **Junior developer friendly** - Explicit, actionable instructions