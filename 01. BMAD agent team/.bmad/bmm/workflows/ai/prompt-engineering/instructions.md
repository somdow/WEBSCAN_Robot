# Prompt Engineering Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Define Prompt Requirements">
  <action>Understand what the prompt needs to accomplish:</action>
  <ask>
    Let's engineer your prompt. Tell me:
    1. What task should this prompt accomplish?
    2. What model will this run on? (GPT-4, Claude, Gemini, etc.)
    3. What inputs will be provided? (user text, data, context)
    4. What output format do you need? (free text, JSON, specific structure)
    5. Any constraints? (length limits, tone, style, forbidden content)
    6. Do you have examples of good/bad outputs?
    7. Is this for a single use or will it be templated with variables?
  </ask>

  **Prompt Types:**
  - Zero-shot: No examples, instruction only
  - Few-shot: Include examples in prompt
  - Chain-of-thought: Step-by-step reasoning
  - Tree-of-thought: Explore multiple paths
  - Role-based: Persona/character assignment
</step>

<step n="2" goal="Design Prompt Structure">
  <action>Build the prompt architecture:</action>

  **Prompt Components:**

  *System Message (if supported)*
  - Role/persona definition
  - Global constraints
  - Output format rules
  - Behavioral guidelines

  *Context Section*
  - Background information
  - Relevant data/documents
  - Previous conversation (if any)

  *Task Instructions*
  - Clear action statement
  - Step-by-step guidance (if needed)
  - Edge case handling

  *Examples (if few-shot)*
  - Input → Output pairs
  - Diverse, representative cases
  - Include edge cases

  *Output Specification*
  - Format requirements
  - Schema (if structured)
  - Length guidance

  **Best Practices:**
  - Be specific, not vague
  - Use delimiters for sections (```, ###, ---)
  - Put instructions before content
  - Specify what NOT to do
  - Use XML tags for structure (Claude)

  <template-output section="design">Document prompt design</template-output>
</step>

<step n="3" goal="Create Test Suite">
  <action>Design tests for prompt validation:</action>

  **Test Categories:**
  - Happy path: Normal, expected inputs
  - Edge cases: Boundary conditions
  - Adversarial: Attempts to break prompt
  - Empty/minimal: Sparse input handling
  - Maximum: Long inputs, stress tests

  **Test Cases (minimum 10-20):**
  - Input scenario
  - Expected output characteristics
  - Pass/fail criteria
  - Priority (critical, important, nice-to-have)

  **Quality Metrics:**
  - Task completion rate
  - Output quality score (1-5 rubric)
  - Format compliance rate
  - Hallucination frequency
  - Consistency (same input → similar output)

  <template-output section="testing">Document test suite</template-output>
</step>

<step n="4" goal="Iterate and Optimize">
  <action>Refine prompt based on testing:</action>

  **Optimization Techniques:**
  - Clarify ambiguous instructions
  - Add/remove examples
  - Adjust temperature/parameters
  - Restructure prompt sections
  - Add guardrails for failure modes

  **Common Issues and Fixes:**
  - Too verbose → Add length constraints
  - Wrong format → Add explicit format spec + example
  - Hallucinations → Add "only use provided info" + citations
  - Inconsistent → Lower temperature, add examples
  - Ignoring instructions → Move instructions, use emphasis
  - Prompt injection → Add delimiters, validate input

  **Version Tracking:**
  - Document each iteration
  - Note what changed and why
  - Track metrics across versions

  <template-output section="optimization">Document optimization process</template-output>
</step>

<step n="5" goal="Production Readiness">
  <action>Prepare prompt for deployment:</action>

  **Production Checklist:**
  - Variable injection points marked
  - Token count estimated
  - Error handling defined
  - Fallback behavior specified
  - Rate limiting considered

  **Documentation:**
  - Prompt purpose and context
  - Variable definitions
  - Expected behavior
  - Known limitations
  - Maintenance notes

  <template-output section="production">Document production readiness</template-output>
</step>

<step n="6" goal="Generate Prompt Engineering Report">
  <action>Compile comprehensive prompt documentation:</action>
  - Final prompt with annotations
  - Design rationale
  - Test results
  - Optimization history
  - Production deployment guide
  - Maintenance recommendations

  <action>Save to output file</action>
  <action>Present final prompt and key metrics</action>
</step>

</workflow>
