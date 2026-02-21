# Funnel Analysis Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Define Funnel Scope">
  <action>Understand the funnel to analyze:</action>
  <ask>
    Let's analyze your funnel. Tell me:
    1. What type of funnel? (marketing, sales, onboarding, feature adoption)
    2. What's the desired end action? (purchase, signup, activation)
    3. What are the current funnel stages?
    4. What's your current conversion rate end-to-end?
    5. Do you have data on each stage's volume?
    6. What's the time period for analysis?
    7. Any segments you want to compare? (by source, device, cohort)
  </ask>

  **Funnel Types:**
  - TOFU → MOFU → BOFU (marketing)
  - Visitor → Lead → MQL → SQL → Customer (sales)
  - Signup → Activation → Engagement → Retention (product)

  <template-output section="scope">Document funnel scope</template-output>
</step>

<step n="2" goal="Map Current Funnel">
  <action>Document the current funnel structure:</action>

  **For Each Stage:**
  - Stage name and definition
  - Entry criteria
  - Exit criteria/action
  - Current volume
  - Conversion to next stage

  **Calculate:**
  - Stage-to-stage conversion rates
  - Overall funnel conversion rate
  - Average time in each stage
  - Drop-off percentages

  <template-output section="current">Document current funnel state</template-output>
</step>

<step n="3" goal="Identify Drop-Off Points">
  <action>Analyze where and why people leave:</action>

  **Drop-Off Analysis:**
  - Biggest absolute drop-offs
  - Biggest percentage drop-offs
  - Unexpected drop-offs vs benchmarks

  **Diagnosis Questions:**
  - Is the ask too big for this stage?
  - Is there friction in the process?
  - Is the value proposition unclear?
  - Are there technical issues?
  - Is targeting bringing wrong audience?

  **Segment Analysis:**
  - By acquisition source
  - By device type
  - By user segment
  - By time/day

  <template-output section="dropoffs">Document drop-off analysis</template-output>
</step>

<step n="4" goal="Benchmark and Compare">
  <action>Compare against benchmarks and segments:</action>

  **Industry Benchmarks:**
  - Research typical conversion rates for your industry
  - Identify where you're above/below benchmark

  **Internal Benchmarks:**
  - Compare time periods (MoM, YoY)
  - Compare segments
  - Compare by source/campaign

  **Gap Analysis:**
  - Calculate potential uplift if hitting benchmarks
  - Prioritize by impact

  <template-output section="benchmarks">Document benchmark analysis</template-output>
</step>

<step n="5" goal="Generate Optimization Recommendations">
  <action>Create prioritized improvement plan:</action>

  **For Each Drop-Off Point:**
  - Root cause hypothesis
  - Recommended tests/changes
  - Expected impact
  - Effort to implement
  - Priority score

  **Optimization Categories:**
  - Copy and messaging
  - UX and design
  - Offer and pricing
  - Targeting and segmentation
  - Technical improvements

  <template-output section="recommendations">Document recommendations</template-output>
</step>

<step n="6" goal="Generate Funnel Analysis Report">
  <action>Compile comprehensive funnel analysis:</action>
  - Executive summary
  - Current funnel visualization
  - Stage-by-stage analysis
  - Drop-off heat map
  - Benchmark comparison
  - Prioritized recommendations
  - Testing roadmap
  - Expected impact projections

  <action>Save to output file</action>
  <action>Present funnel health and top optimization opportunities</action>
</step>

</workflow>
