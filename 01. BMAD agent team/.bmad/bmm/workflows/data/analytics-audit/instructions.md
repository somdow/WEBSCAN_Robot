# Analytics Audit Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Discover Current Analytics Stack">
  <action>Identify all analytics tools and implementations:</action>
  <ask>
    Let's audit your analytics setup. Tell me:
    1. What analytics platforms do you use? (GA4, Mixpanel, Amplitude, etc.)
    2. Where is tracking code implemented? (Tag Manager, direct, server-side)
    3. Do you have a data warehouse? (BigQuery, Snowflake, Redshift)
    4. What reporting tools do you use? (Looker, Tableau, custom dashboards)
    5. Any existing documentation on tracked events?
    6. Who owns analytics in your organization?
  </ask>

  **Discovery Checklist:**
  - Analytics platforms identified
  - Implementation method documented
  - Data flow mapped
  - Stakeholders identified
</step>

<step n="2" goal="Audit Tracking Implementation">
  <action>Evaluate tracking code quality and coverage:</action>

  **Page-Level Tracking:**
  - Page view tracking on all pages
  - Virtual page views for SPAs
  - Proper page title and URL capture
  - Referrer tracking

  **Event Tracking:**
  - User interactions captured
  - Conversion events defined
  - Event naming conventions
  - Parameter consistency

  **User Identification:**
  - User ID implementation
  - Cross-device tracking
  - Session stitching
  - PII handling compliance

  <template-output section="implementation">Document tracking implementation findings</template-output>
</step>

<step n="3" goal="Assess Data Quality">
  <action>Evaluate accuracy and reliability of collected data:</action>

  **Data Accuracy:**
  - Duplicate event detection
  - Bot traffic filtering
  - Internal traffic exclusion
  - Sampling impact

  **Data Completeness:**
  - Missing required parameters
  - Null/undefined values
  - Coverage gaps by platform/page
  - Event firing consistency

  **Data Freshness:**
  - Real-time vs batch processing
  - Latency in reporting
  - Historical data availability

  <template-output section="quality">Document data quality findings</template-output>
</step>

<step n="4" goal="Review Event Taxonomy">
  <action>Evaluate event naming and structure:</action>

  **Naming Conventions:**
  - Consistent naming patterns
  - Clear, descriptive names
  - Namespace organization
  - Version tracking

  **Event Structure:**
  - Required vs optional parameters
  - Data types consistency
  - Enumerated values documented
  - Schema validation

  **Documentation:**
  - Event dictionary exists
  - Up-to-date documentation
  - Change log maintained
  - Team accessibility

  <template-output section="taxonomy">Document taxonomy findings</template-output>
</step>

<step n="5" goal="Evaluate Business Goal Alignment">
  <action>Assess if tracking supports business objectives:</action>

  **Key Questions:**
  - Are business KPIs measurable with current tracking?
  - Can you answer critical business questions?
  - Are conversion funnels properly instrumented?
  - Is attribution tracking adequate?

  **Gap Analysis:**
  - What questions can't you answer today?
  - What decisions lack data support?
  - Where is tracking missing or insufficient?

  <template-output section="alignment">Document business alignment findings</template-output>
</step>

<step n="6" goal="Generate Audit Report">
  <action>Compile comprehensive analytics audit:</action>
  - Executive summary with health score
  - Current state documentation
  - Data quality assessment
  - Gap analysis with priorities
  - Remediation roadmap
  - Quick wins vs long-term improvements

  <action>Save to output file</action>
  <action>Present analytics health score and top recommendations</action>
</step>

</workflow>
