# Containerization Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Assess Current State">
  <action>Gather application details:</action>
  <ask>
    Let's containerize your application. Tell me:
    1. What's your application stack? (language, framework, runtime version)
    2. What dependencies does it need? (databases, caches, queues)
    3. Current deployment method? (bare metal, VMs, already containerized)
    4. How many services/components?
    5. Target platform? (Docker only, Kubernetes, ECS, Cloud Run)
    6. Any stateful components? (databases, file storage)
  </ask>
</step>

<step n="2" goal="Design Container Architecture">
  <action>Design the container structure:</action>

  **Service Decomposition:**
  - Which components become containers
  - Shared vs isolated dependencies
  - Sidecar patterns (logging, proxies)

  **Image Strategy:**
  - Base image selection (Alpine, Debian, distroless)
  - Multi-stage builds
  - Layer optimization
  - Image tagging strategy

  **Networking:**
  - Inter-container communication
  - Service discovery
  - Load balancing
  - Ingress/egress

  <template-output section="architecture">Document container architecture</template-output>
</step>

<step n="3" goal="Create Dockerfile(s)">
  <action>Generate optimized Dockerfiles:</action>

  **Best Practices:**
  - Multi-stage builds to reduce image size
  - Non-root user execution
  - Proper .dockerignore
  - Health checks
  - Signal handling (SIGTERM)
  - Minimal layers
  - Deterministic builds (lock files)

  <template-output section="dockerfiles">Provide Dockerfile(s)</template-output>
</step>

<step n="4" goal="Orchestration Configuration">
  <action>Create orchestration configs:</action>

  **Docker Compose (Development):**
  - All services defined
  - Volume mounts for development
  - Environment variable handling
  - Network configuration

  **Kubernetes (Production) - if applicable:**
  - Deployment manifests
  - Service definitions
  - ConfigMaps and Secrets
  - Ingress rules
  - Resource limits
  - Horizontal Pod Autoscaler

  <template-output section="orchestration">Provide orchestration configs</template-output>
</step>

<step n="5" goal="Security Hardening">
  <action>Apply container security best practices:</action>

  **Image Security:**
  - Vulnerability scanning setup
  - Trusted base images
  - No secrets in images
  - Read-only filesystems where possible

  **Runtime Security:**
  - Resource limits (CPU, memory)
  - Security contexts (non-root, capabilities)
  - Network policies
  - Pod security policies/standards

  <template-output section="security">Document security measures</template-output>
</step>

<step n="6" goal="Generate Documentation">
  <action>Create operational documentation:</action>

  - Local development setup
  - Building images
  - Running containers
  - Debugging containers
  - Production deployment
  - Scaling guidelines

  <action>Save complete documentation to output file</action>
</step>

</workflow>
