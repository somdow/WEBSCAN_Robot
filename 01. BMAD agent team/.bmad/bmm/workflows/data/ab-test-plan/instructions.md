# A/B Test Plan Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Define the Experiment">
  <action>Understand what you're testing and why:</action>
  <ask>
    Let's design your A/B test. Tell me:
    1. What change are you testing? (new feature, design change, copy variation)
    2. Why do you believe this change will improve outcomes?
    3. What is the primary metric you want to improve?
    4. What's the current baseline for that metric?
    5. What's the minimum improvement that would be worth implementing?
    6. Are there any secondary metrics to monitor?
    7. What's your current traffic volume?
  </ask>

  **Experiment Definition:**
  - Clear hypothesis
  - Business justification
  - Success criteria
</step>

<step n="2" goal="Formulate Hypothesis">
  <action>Create a testable hypothesis:</action>

  **Hypothesis Structure:**
  "If we [change], then [metric] will [improve/increase/decrease] by [amount] because [reason]."

  **Components:**
  - Independent variable (what you're changing)
  - Dependent variable (what you're measuring)
  - Expected direction of change
  - Theoretical basis

  **Null Hypothesis (H0):**
  There is no difference between control and treatment.

  **Alternative Hypothesis (H1):**
  The treatment produces a statistically significant difference.

  <template-output section="hypothesis">Document hypothesis</template-output>
</step>

<step n="3" goal="Calculate Sample Size">
  <action>Determine required sample size for statistical power:</action>

  **Required Inputs:**
  - Baseline conversion rate
  - Minimum Detectable Effect (MDE)
  - Statistical significance level (typically 95%, α = 0.05)
  - Statistical power (typically 80%, β = 0.20)

  **Sample Size Formula Considerations:**
  - Two-tailed vs one-tailed test
  - Binary vs continuous metrics
  - Traffic split ratio

  **Duration Calculation:**
  - Daily traffic volume
  - Required sample size
  - Full business cycles (include weekends)

  <template-output section="sample">Document sample size calculation</template-output>
</step>

<step n="4" goal="Design Test Structure">
  <action>Define test mechanics:</action>

  **Traffic Allocation:**
  - Split ratio (typically 50/50)
  - Randomization method
  - User assignment persistence

  **Test Groups:**
  - Control: Current experience
  - Treatment: Modified experience
  - (Optional) Multiple treatments

  **Segmentation:**
  - User segments to include/exclude
  - Device types
  - Geographic regions
  - New vs returning users

  **Guardrail Metrics:**
  - Metrics that should NOT decrease
  - Stopping rules if guardrails are violated

  <template-output section="design">Document test design</template-output>
</step>

<step n="5" goal="Plan Analysis">
  <action>Define how results will be analyzed:</action>

  **Statistical Tests:**
  - Binary metrics: Chi-square, Z-test for proportions
  - Continuous metrics: T-test, Mann-Whitney U
  - Multiple comparisons: Bonferroni correction

  **Analysis Timeline:**
  - No peeking before minimum sample size
  - Scheduled analysis checkpoints
  - Final analysis date

  **Interpretation Guidelines:**
  - p-value threshold
  - Confidence interval interpretation
  - Practical significance vs statistical significance

  **Decision Framework:**
  - Ship if: conditions
  - Iterate if: conditions
  - Kill if: conditions

  <template-output section="analysis">Document analysis plan</template-output>
</step>

<step n="6" goal="Generate A/B Test Plan Document">
  <action>Compile complete test plan:</action>
  - Executive summary
  - Hypothesis and rationale
  - Sample size and duration
  - Test design specifications
  - Analysis plan
  - Timeline and milestones
  - Risk mitigation

  <action>Save to output file</action>
  <action>Present test plan summary with key parameters</action>
</step>

</workflow>
