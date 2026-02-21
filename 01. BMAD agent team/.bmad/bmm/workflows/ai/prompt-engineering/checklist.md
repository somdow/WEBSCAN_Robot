# Prompt Engineering Validation Checklist

## Requirements
- [ ] Task clearly defined
- [ ] Input format specified
- [ ] Output format specified
- [ ] Constraints documented
- [ ] Target model identified

## Prompt Design
- [ ] Clear task instruction
- [ ] Appropriate structure (delimiters, sections)
- [ ] Output format specified explicitly
- [ ] Examples included (if few-shot)
- [ ] Edge cases addressed
- [ ] Guardrails for failure modes

## Test Suite
- [ ] Minimum 10 test cases
- [ ] Happy path covered
- [ ] Edge cases covered
- [ ] Adversarial cases tested
- [ ] Scoring rubric defined
- [ ] Pass/fail criteria clear

## Optimization
- [ ] Multiple iterations tested
- [ ] Changes documented
- [ ] Metrics improved or stable
- [ ] Common issues addressed

## Production Readiness
- [ ] Variables clearly marked
- [ ] Token count estimated
- [ ] Model parameters specified
- [ ] Error handling defined
- [ ] Fallback behavior documented

## Documentation
- [ ] Design rationale explained
- [ ] Known limitations noted
- [ ] Maintenance guidance provided
- [ ] Full prompt copy-paste ready

## Quality Metrics
- [ ] Pass rate acceptable (>90%)
- [ ] Quality score meets threshold
- [ ] Format compliance high
- [ ] Consistency verified
