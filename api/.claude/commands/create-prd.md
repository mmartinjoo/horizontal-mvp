# Claude Code: Product Requirements Document (PRD) Generator

## Overview
This workflow guides Claude Code in creating detailed Product Requirements Documents (PRDs) in Markdown format based on user prompts. The PRD should be clear, actionable, and suitable for junior developers to implement.

## Workflow

### Phase 1: Initial Prompt Reception
When a user requests a new feature or provides a brief description:

1. **Acknowledge the request** and explain you'll need to gather more details
2. **Ask clarifying questions** before writing the PRD
3. **Present options as numbered/lettered lists** for easy selection

### Phase 2: Clarifying Questions
Adapt questions based on the prompt, covering these key areas:

**Problem & Goals:**
- What problem does this feature solve for users?
- What's the main goal we want to achieve?

**Target Users:**
- Who is the primary user of this feature?
- What's their typical workflow/context?

**Core Functionality:**
- What key actions should users be able to perform?
- Can you provide 2-3 user stories? (As a [user type], I want to [action] so that [benefit])

**Success Criteria:**
- How will we know this feature is successfully implemented?
- What are the key acceptance criteria?

**Scope & Boundaries:**
- What should this feature NOT do (non-goals)?
- Any specific constraints or limitations?

**Technical Context:**
- Any existing systems/components to integrate with?
- Data requirements or sources?
- Design guidelines or mockups available?

### Phase 3: PRD Generation
After gathering requirements, create a PRD with this structure:

```markdown
# PRD: [Feature Name]

## 1. Introduction/Overview
Brief description of the feature and problem it solves.

## 2. Goals
- Specific, measurable objectives
- Clear success criteria

## 3. User Stories
- As a [user type], I want to [action] so that [benefit]
- [Additional user stories]

## 4. Functional Requirements
1. The system must [specific requirement]
2. Users shall be able to [specific functionality]
3. [Additional numbered requirements]

## 5. Non-Goals (Out of Scope)
- What this feature will NOT include
- Clear scope boundaries

## 6. Design Considerations
- UI/UX requirements
- Existing component references
- Accessibility considerations

## 7. Technical Considerations
- Integration points
- Dependencies
- Performance requirements

## 8. Success Metrics
- How success will be measured
- Key performance indicators

## 9. Open Questions
- Remaining clarifications needed
- Areas requiring further research
```

### Phase 4: File Creation
1. **Create the file** at `./tasks/prd-[feature-name].md`
2. **Confirm file creation** with the user
3. **Ask if they want to proceed** to task generation

## Target Audience
Write for **junior developers** - be explicit, unambiguous, and avoid unnecessary jargon.

## Command Examples
```bash
# Create PRD directory if it doesn't exist
mkdir -p tasks

# Generate PRD file
echo "Creating PRD for [feature-name]..."
# [PRD content generation]

# Confirm completion
echo "PRD saved to ./tasks/prd-[feature-name].md"
```

## Key Principles
1. **Ask before implementing** - Gather requirements first
2. **Structure responses clearly** - Use numbered options for user selection
3. **Write actionable requirements** - Clear, testable specifications
4. **Maintain scope discipline** - Clear non-goals prevent scope creep