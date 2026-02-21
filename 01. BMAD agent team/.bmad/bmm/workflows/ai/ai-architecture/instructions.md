# AI Architecture Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Define AI System Requirements">
  <action>Understand what the AI system needs to accomplish:</action>
  <ask>
    Let's design your AI architecture. Tell me:
    1. What capabilities does your AI system need? (Q&A, generation, analysis, agents)
    2. What data sources will it work with? (documents, databases, APIs, real-time)
    3. What are your latency requirements? (real-time, near-real-time, batch)
    4. What's your expected scale? (requests per day, data volume)
    5. What's your reliability requirement? (uptime, fallback needs)
    6. Any existing infrastructure to integrate with?
    7. What's your team's ML/AI expertise level?
  </ask>

  **Architecture Patterns:**
  - Simple API calls (direct LLM integration)
  - RAG (Retrieval-Augmented Generation)
  - Agents (autonomous task completion)
  - Pipelines (multi-step processing)
  - Hybrid (combining patterns)
</step>

<step n="2" goal="Design Data Architecture">
  <action>Plan how data flows through the system:</action>

  **Data Ingestion:**
  - Source types (files, APIs, databases, streams)
  - Ingestion frequency (batch, real-time, on-demand)
  - Preprocessing pipeline
  - Data validation

  **Vector Storage (if RAG):**
  - Embedding model selection
  - Chunking strategy
  - Vector database choice (Pinecone, Weaviate, ChromaDB, pgvector)
  - Index optimization
  - Update/refresh strategy

  **Context Management:**
  - Context window utilization
  - Memory/history handling
  - Session management
  - Cache layers

  <template-output section="data">Document data architecture</template-output>
</step>

<step n="3" goal="Design Processing Architecture">
  <action>Define how requests are processed:</action>

  **Request Flow:**
  - Input validation
  - Query preprocessing
  - Retrieval (if RAG)
  - Prompt construction
  - Model inference
  - Output postprocessing
  - Response formatting

  **Multi-Step Processing (if needed):**
  - Pipeline stages
  - Conditional branching
  - Parallel processing
  - Error handling per stage

  **Agent Architecture (if needed):**
  - Tool definitions
  - Planning strategy
  - Execution loop
  - Safety guardrails
  - Human-in-the-loop points

  <template-output section="processing">Document processing architecture</template-output>
</step>

<step n="4" goal="Design Infrastructure">
  <action>Plan deployment and operational architecture:</action>

  **Compute Architecture:**
  - API layer design
  - Async vs sync processing
  - Queue management
  - Auto-scaling strategy

  **Reliability Patterns:**
  - Fallback models
  - Circuit breakers
  - Retry strategies
  - Graceful degradation
  - Multi-provider redundancy

  **Observability:**
  - Logging strategy
  - Metrics collection
  - Tracing
  - Quality monitoring
  - Cost tracking

  **Security:**
  - Input sanitization
  - Output filtering
  - Rate limiting
  - Authentication
  - Data privacy

  <template-output section="infrastructure">Document infrastructure architecture</template-output>
</step>

<step n="5" goal="Create Implementation Plan">
  <action>Build actionable implementation roadmap:</action>

  **MVP Architecture:**
  - Minimum viable components
  - Fastest path to value
  - Technical debt acceptance

  **Full Architecture:**
  - Complete component list
  - Integration points
  - Migration path from MVP

  **Technology Selection:**
  - Frameworks (LangChain, LlamaIndex, custom)
  - Vector databases
  - Orchestration tools
  - Monitoring stack

  <template-output section="implementation">Document implementation plan</template-output>
</step>

<step n="6" goal="Generate AI Architecture Document">
  <action>Compile comprehensive architecture documentation:</action>
  - Executive summary
  - System overview diagram
  - Data architecture
  - Processing architecture
  - Infrastructure design
  - Technology choices with rationale
  - Implementation roadmap
  - Operational considerations

  <action>Save to output file</action>
  <action>Present architecture summary and key decisions</action>
</step>

</workflow>
