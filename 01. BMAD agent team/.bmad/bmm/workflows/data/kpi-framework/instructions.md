# KPI Framework Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Understand Business Context">
  <action>Gather business objectives and strategic priorities:</action>
  <ask>
    Let's build your KPI framework. Tell me:
    1. What are your top 3-5 business objectives this quarter/year?
    2. What does success look like for your product/company?
    3. Who are the key stakeholders who will use these metrics?
    4. What decisions will these metrics inform?
    5. What metrics do you currently track (if any)?
    6. What's your business model? (SaaS, e-commerce, marketplace, etc.)
  </ask>

  **Framework Foundation:**
  - Business model understood
  - Strategic objectives documented
  - Stakeholder needs mapped
  - Decision contexts identified
</step>

<step n="2" goal="Define North Star Metric">
  <action>Identify the single most important metric:</action>

  **North Star Criteria:**
  - Reflects core value delivered to customers
  - Leading indicator of long-term success
  - Actionable by multiple teams
  - Easy to understand

  **Common North Star Examples:**
  - SaaS: Weekly Active Users, MRR
  - E-commerce: Purchases per month
  - Marketplace: Transactions completed
  - Content: Time spent, articles read

  **Validation Questions:**
  - Does growing this metric mean the business is healthy?
  - Can teams across the org influence it?
  - Does it align with customer value?

  <template-output section="northstar">Document North Star metric</template-output>
</step>

<step n="3" goal="Build Metrics Hierarchy">
  <action>Create structured metrics pyramid:</action>

  **Level 1: North Star**
  - Single most important metric
  - Reviewed by executives

  **Level 2: Strategic KPIs (3-5)**
  - Direct drivers of North Star
  - Owned by department heads
  - Examples: Acquisition, Activation, Retention, Revenue, Referral

  **Level 3: Tactical Metrics (per KPI)**
  - Specific, actionable metrics
  - Owned by team leads
  - Leading indicators

  **Level 4: Operational Metrics**
  - Day-to-day monitoring
  - Early warning signals

  <template-output section="hierarchy">Document metrics hierarchy</template-output>
</step>

<step n="4" goal="Set Targets and Benchmarks">
  <action>Establish measurement targets:</action>

  **Target Setting Methods:**
  - Historical baseline + improvement %
  - Industry benchmarks
  - Competitor analysis
  - Bottom-up from team capacity
  - Top-down from business goals

  **For Each KPI Define:**
  - Current baseline
  - Target (short-term: 30/60/90 days)
  - Stretch goal
  - Minimum acceptable threshold
  - Industry benchmark (if available)

  <template-output section="targets">Document targets and benchmarks</template-output>
</step>

<step n="5" goal="Define Measurement Strategy">
  <action>Establish how metrics will be tracked:</action>

  **For Each Metric:**
  - Data source
  - Calculation formula
  - Tracking frequency
  - Owner responsible
  - Review cadence

  **Segmentation Strategy:**
  - By user cohort
  - By acquisition channel
  - By product area
  - By geography
  - By customer tier

  **Alerting Rules:**
  - Threshold for alerts
  - Escalation path
  - Response playbook

  <template-output section="measurement">Document measurement strategy</template-output>
</step>

<step n="6" goal="Generate KPI Framework Document">
  <action>Compile comprehensive KPI framework:</action>
  - Executive summary
  - North Star definition
  - Complete metrics hierarchy
  - Targets and benchmarks
  - Measurement strategy
  - Implementation roadmap
  - Governance model

  <action>Save to output file</action>
  <action>Present framework summary and implementation next steps</action>
</step>

</workflow>
