# Infrastructure Audit Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Gather Infrastructure Context">
  <action>Request infrastructure details:</action>
  <ask>
    Let's audit your infrastructure. Tell me:
    1. What's the current hosting setup? (cloud provider, on-prem, hybrid)
    2. What's the tech stack? (languages, frameworks, databases)
    3. Current scale? (users, requests/day, data volume)
    4. Any known pain points or concerns?
    5. Do you have architecture diagrams or documentation I can review?
  </ask>
  <action>If project docs exist (architecture.md), offer to review them</action>
</step>

<step n="2" goal="Architecture Assessment">
  <action>Evaluate overall architecture:</action>

  **Component Analysis:**
  - Application tier (monolith, microservices, serverless)
  - Data tier (databases, caches, queues)
  - Network topology (VPCs, subnets, load balancers)
  - External integrations (APIs, third-party services)

  **Scalability Review:**
  - Horizontal vs vertical scaling capability
  - Bottleneck identification
  - Auto-scaling configuration
  - Stateless vs stateful components

  <template-output section="architecture">Document architecture findings</template-output>
</step>

<step n="3" goal="Reliability & Availability">
  <action>Assess reliability patterns:</action>

  **High Availability:**
  - Single points of failure (SPOF)
  - Redundancy at each tier
  - Failover mechanisms
  - Multi-region/multi-AZ deployment

  **Disaster Recovery:**
  - Backup strategy and frequency
  - Recovery Time Objective (RTO)
  - Recovery Point Objective (RPO)
  - Tested restore procedures

  **Resilience Patterns:**
  - Circuit breakers
  - Retry logic with backoff
  - Graceful degradation
  - Health checks

  <template-output section="reliability">Document reliability findings</template-output>
</step>

<step n="4" goal="Performance Analysis">
  <action>Evaluate performance characteristics:</action>

  **Current Performance:**
  - Response times (P50, P95, P99)
  - Throughput capacity
  - Resource utilization (CPU, memory, disk, network)
  - Database query performance

  **Performance Risks:**
  - N+1 query patterns
  - Missing indexes
  - Unbounded queries
  - Memory leaks
  - Connection pool exhaustion

  <template-output section="performance">Document performance findings</template-output>
</step>

<step n="5" goal="Security Posture">
  <action>Review infrastructure security:</action>

  **Network Security:**
  - Firewall rules and security groups
  - Network segmentation
  - Private vs public subnets
  - VPN/bastion access

  **Data Security:**
  - Encryption at rest
  - Encryption in transit
  - Secrets management
  - Access controls (IAM)

  **Compliance:**
  - Logging and audit trails
  - Data retention policies
  - Regulatory requirements

  <template-output section="security">Document security findings</template-output>
</step>

<step n="6" goal="Cost Optimization">
  <action>Analyze infrastructure costs:</action>

  **Current Spend:**
  - Compute costs
  - Storage costs
  - Network/egress costs
  - Third-party service costs

  **Optimization Opportunities:**
  - Right-sizing instances
  - Reserved instances vs on-demand
  - Spot/preemptible instances
  - Unused resources
  - Storage tier optimization

  <template-output section="cost">Document cost findings</template-output>
</step>

<step n="7" goal="Generate Audit Report">
  <action>Compile findings into prioritized recommendations:</action>

  **Priority Levels:**
  - Critical: Immediate risk to availability or security
  - High: Significant impact on reliability or cost
  - Medium: Improvements for scalability or performance
  - Low: Nice-to-have optimizations

  <action>Save report to output file</action>
  <action>Present executive summary with top 5 priorities</action>
</step>

</workflow>
