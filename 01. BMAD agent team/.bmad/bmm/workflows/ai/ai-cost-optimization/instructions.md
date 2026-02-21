# AI Cost Optimization Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Audit Current AI Costs">
  <action>Understand current AI spend and usage patterns:</action>
  <ask>
    Let's optimize your AI costs. Tell me:
    1. Which AI APIs are you using? (OpenAI, Anthropic, Google, etc.)
    2. What's your current monthly AI spend?
    3. What are your main use cases? (list each)
    4. What's your current request volume per use case?
    5. Do you have access to usage logs/analytics?
    6. What models are you using for each use case?
    7. What's your quality threshold? (can't sacrifice vs flexible)
  </ask>

  **Cost Components:**
  - Input tokens
  - Output tokens
  - Requests (if applicable)
  - Fine-tuning (if applicable)
  - Embedding storage (if applicable)
</step>

<step n="2" goal="Analyze Usage Patterns">
  <action>Identify cost drivers and optimization opportunities:</action>

  **Usage Analysis:**
  - Requests by use case
  - Average tokens per request (input + output)
  - Peak vs off-peak patterns
  - Retry/error rates
  - Cache hit potential

  **Cost Distribution:**
  - Cost by use case
  - Cost by model
  - Input vs output token ratio
  - Wasted tokens (errors, retries, unused)

  **High-Impact Areas:**
  - Largest cost centers
  - Highest token-per-request operations
  - Most frequent operations
  - Operations with poor quality ROI

  <template-output section="analysis">Document usage analysis</template-output>
</step>

<step n="3" goal="Identify Optimization Strategies">
  <action>Develop cost reduction approaches:</action>

  **Token Reduction:**
  - Prompt compression techniques
  - Remove verbose instructions
  - Use shorter examples
  - Limit output length
  - Extract only needed data

  **Model Optimization:**
  - Downgrade where quality permits
  - Model routing (cheap for simple, expensive for complex)
  - Use specialized models vs general purpose

  **Caching Strategies:**
  - Semantic caching for similar queries
  - Response caching for identical requests
  - Embedding caching
  - Precomputed responses for common queries

  **Architecture Changes:**
  - Batch processing where possible
  - Async processing for non-real-time
  - Client-side filtering before API calls
  - Chunking optimization

  **Rate and Tier Optimization:**
  - Committed use discounts
  - Batch API pricing
  - Fine-tuning vs few-shot trade-offs

  <template-output section="strategies">Document optimization strategies</template-output>
</step>

<step n="4" goal="Quantify Savings">
  <action>Calculate potential cost reduction:</action>

  **For Each Strategy:**
  - Current cost baseline
  - Estimated cost after optimization
  - Savings percentage
  - Implementation effort
  - Quality impact risk

  **ROI Analysis:**
  - One-time implementation cost
  - Monthly savings
  - Payback period
  - Risk-adjusted savings

  **Prioritization Matrix:**
  - High savings + Low effort = Do immediately
  - High savings + High effort = Plan carefully
  - Low savings + Low effort = Quick wins
  - Low savings + High effort = Deprioritize

  <template-output section="savings">Document savings estimates</template-output>
</step>

<step n="5" goal="Create Implementation Plan">
  <action>Build actionable optimization roadmap:</action>

  **Phase 1: Quick Wins (Week 1-2)**
  - Prompt optimization
  - Output length limits
  - Basic caching

  **Phase 2: Model Optimization (Week 3-4)**
  - Model downgrade testing
  - Routing implementation
  - A/B testing framework

  **Phase 3: Architecture (Week 5+)**
  - Caching infrastructure
  - Batch processing
  - Monitoring/alerting

  **Quality Gates:**
  - Metrics to monitor
  - Rollback triggers
  - A/B test requirements

  <template-output section="implementation">Document implementation plan</template-output>
</step>

<step n="6" goal="Generate Cost Optimization Report">
  <action>Compile comprehensive optimization plan:</action>
  - Executive summary with projected savings
  - Current cost breakdown
  - Usage analysis
  - Optimization strategies
  - Savings projections
  - Implementation roadmap
  - Monitoring plan
  - Quality safeguards

  <action>Save to output file</action>
  <action>Present top savings opportunities and quick wins</action>
</step>

</workflow>
