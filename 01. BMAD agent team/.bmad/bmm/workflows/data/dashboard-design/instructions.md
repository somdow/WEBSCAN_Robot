# Dashboard Design Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Define Dashboard Purpose and Audience">
  <action>Understand who will use the dashboard and why:</action>
  <ask>
    Let's design your dashboard. Tell me:
    1. Who is the primary audience? (executives, managers, analysts, ops)
    2. What decisions will this dashboard inform?
    3. How often will they check it? (real-time, daily, weekly)
    4. What questions should it answer at a glance?
    5. What existing reports/dashboards do they use today?
    6. What's the viewing context? (desktop, mobile, TV display)
  </ask>

  **Audience Profiles:**
  - Executive: High-level KPIs, trends, exceptions
  - Manager: Team performance, progress to goals
  - Analyst: Detailed metrics, drill-down capability
  - Operations: Real-time status, alerts, queues

  <template-output section="purpose">Document dashboard purpose and audience</template-output>
</step>

<step n="2" goal="Select Key Metrics">
  <action>Choose the right metrics for the dashboard:</action>

  **Metric Selection Criteria:**
  - Directly supports decision-making
  - Actionable (viewer can do something about it)
  - Up-to-date (refresh frequency matches need)
  - Understandable (no explanation needed)

  **Avoid:**
  - Vanity metrics
  - Too many metrics (5-9 max for primary view)
  - Metrics without context
  - Lagging indicators only

  **For Each Metric:**
  - Name and definition
  - Why it matters
  - Target/benchmark
  - Comparison context (vs last period, vs goal)

  <template-output section="metrics">Document selected metrics</template-output>
</step>

<step n="3" goal="Choose Visualization Types">
  <action>Select appropriate chart types for each metric:</action>

  **Visualization Guide:**
  - **Single KPI**: Big number with trend indicator
  - **Trend over time**: Line chart
  - **Comparison**: Bar chart (horizontal for labels)
  - **Part of whole**: Pie/donut (limit to 5 segments)
  - **Distribution**: Histogram
  - **Correlation**: Scatter plot
  - **Geographic**: Map
  - **Progress**: Gauge, progress bar
  - **Tables**: Only for detailed lookup

  **Best Practices:**
  - Start Y-axis at zero for bars
  - Limit colors (use meaningfully)
  - Add context (benchmarks, goals)
  - Show trend direction

  <template-output section="visualizations">Document visualization choices</template-output>
</step>

<step n="4" goal="Design Layout and Hierarchy">
  <action>Create logical information architecture:</action>

  **Layout Principles:**
  - Most important metrics top-left
  - Follow F-pattern reading
  - Group related metrics
  - Progressive disclosure (summary → detail)

  **Information Hierarchy:**
  1. Primary KPIs (largest, top)
  2. Supporting metrics (medium)
  3. Detail/drill-down (smaller, below)

  **Layout Patterns:**
  - Executive: 3-4 big numbers + one trend chart
  - Operational: Status overview + queues + alerts
  - Analytical: Filters + charts + detail tables

  <template-output section="layout">Document layout design</template-output>
</step>

<step n="5" goal="Define Interactivity and Filters">
  <action>Plan dashboard interactivity:</action>

  **Filter Strategy:**
  - Date range (always include)
  - Key dimensions (segment, region, product)
  - Saved views/presets

  **Drill-Down:**
  - Click-to-filter
  - Linked dashboards
  - Detail modals

  **Alerts and Highlights:**
  - Conditional formatting
  - Threshold indicators
  - Exception highlighting

  <template-output section="interactivity">Document interactivity design</template-output>
</step>

<step n="6" goal="Generate Dashboard Specification">
  <action>Compile complete dashboard design document:</action>
  - Purpose and audience definition
  - Metric specifications
  - Visualization wireframes
  - Layout mockup
  - Interactivity requirements
  - Data requirements
  - Implementation notes

  <action>Save to output file</action>
  <action>Present dashboard design summary</action>
</step>

</workflow>
