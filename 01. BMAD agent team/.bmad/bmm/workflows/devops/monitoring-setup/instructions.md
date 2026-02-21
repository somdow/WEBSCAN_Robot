# Monitoring Setup Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Assess Observability Needs">
  <action>Gather requirements:</action>
  <ask>
    Let's set up monitoring and observability. Tell me:
    1. What's your current stack? (infrastructure, applications)
    2. Any existing monitoring tools?
    3. What are you most worried about? (uptime, performance, errors)
    4. Who needs access to monitoring? (devs, ops, business)
    5. Budget constraints? (open source vs managed services)
    6. Compliance requirements? (log retention, audit trails)
  </ask>
</step>

<step n="2" goal="Design Metrics Strategy">
  <action>Define metrics collection:</action>

  **Infrastructure Metrics:**
  - CPU, memory, disk, network
  - Container metrics (if applicable)
  - Database metrics
  - Cache hit rates

  **Application Metrics:**
  - Request rate (throughput)
  - Error rate
  - Duration/latency (P50, P95, P99)
  - Saturation (queue depths, connection pools)

  **Business Metrics:**
  - User signups, conversions
  - Feature usage
  - Revenue-impacting events

  **RED Method (Request, Error, Duration):**
  - Apply to every service endpoint

  <template-output section="metrics">Document metrics strategy</template-output>
</step>

<step n="3" goal="Design Logging Strategy">
  <action>Define logging architecture:</action>

  **Log Levels:**
  - ERROR: Failures requiring attention
  - WARN: Potential issues
  - INFO: Business events, state changes
  - DEBUG: Development troubleshooting

  **Structured Logging:**
  - JSON format for parseability
  - Correlation IDs for tracing
  - Consistent field names
  - No PII in logs

  **Log Aggregation:**
  - Centralized collection
  - Retention policies
  - Search and analysis

  <template-output section="logging">Document logging strategy</template-output>
</step>

<step n="4" goal="Design Tracing Strategy">
  <action>Define distributed tracing:</action>

  **Trace Propagation:**
  - Trace ID across services
  - Span hierarchy
  - Context propagation headers

  **What to Trace:**
  - HTTP requests
  - Database queries
  - External API calls
  - Queue operations

  **Sampling Strategy:**
  - Head-based vs tail-based
  - Sample rate by traffic volume

  <template-output section="tracing">Document tracing strategy</template-output>
</step>

<step n="5" goal="Design Alerting Strategy">
  <action>Define alerting rules:</action>

  **Alert Severity:**
  - Critical: Immediate response required (pager)
  - Warning: Investigation needed (ticket)
  - Info: Awareness only (dashboard)

  **Alert Design Principles:**
  - Alert on symptoms, not causes
  - Actionable alerts only
  - Avoid alert fatigue
  - Clear runbook links

  **SLOs and Error Budgets:**
  - Define service level objectives
  - Alert on error budget burn rate

  <template-output section="alerting">Document alerting strategy</template-output>
</step>

<step n="6" goal="Select Tools & Generate Config">
  <action>Recommend observability stack:</action>

  **Options:**
  | Category | Open Source | Managed |
  |----------|-------------|---------|
  | Metrics | Prometheus + Grafana | Datadog, New Relic |
  | Logging | ELK, Loki | Datadog, Splunk |
  | Tracing | Jaeger, Zipkin | Datadog, Honeycomb |
  | Alerting | Alertmanager | PagerDuty, OpsGenie |

  <action>Generate configuration for selected tools</action>
  <action>Save complete documentation to output file</action>
</step>

</workflow>
