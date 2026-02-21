# Model Selection Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Define Use Case Requirements">
  <action>Understand what the AI needs to accomplish:</action>
  <ask>
    Let's find the right model for your use case. Tell me:
    1. What task does the AI need to perform? (text generation, classification, extraction, summarization, coding, etc.)
    2. What's the input format? (short text, long documents, structured data, images, code)
    3. What's the expected output? (free text, JSON, specific format, choices)
    4. What's your latency requirement? (real-time <1s, interactive <5s, batch OK)
    5. What's your budget constraint? (cost per 1K requests acceptable)
    6. Any compliance/privacy requirements? (data residency, no external APIs)
    7. Expected volume? (requests per day/month)
  </ask>

  **Task Categories:**
  - Generation: Creative writing, content creation
  - Analysis: Classification, sentiment, extraction
  - Transformation: Summarization, translation, reformatting
  - Reasoning: Complex logic, multi-step problems
  - Code: Generation, review, debugging
  - Multimodal: Vision, audio integration
</step>

<step n="2" goal="Assess Quality Requirements">
  <action>Define quality and capability needs:</action>

  **Quality Dimensions:**
  - Accuracy: How correct must outputs be?
  - Consistency: Same input → same output?
  - Creativity: Novel vs predictable responses?
  - Following instructions: Complex prompt adherence?
  - Domain knowledge: Specialized expertise needed?

  **Capability Requirements:**
  - Context window: How much input/history needed?
  - Output length: Short responses vs long-form?
  - Structured output: JSON mode, function calling?
  - Tool use: Need to call external functions?
  - Reasoning: Chain-of-thought, step-by-step?

  <template-output section="requirements">Document quality requirements</template-output>
</step>

<step n="3" goal="Evaluate Model Options">
  <action>Compare available models against requirements:</action>

  **Model Tiers:**

  *Frontier (Highest Capability)*
  - GPT-4o, Claude 3.5 Sonnet/Opus, Gemini 1.5 Pro
  - Best for: Complex reasoning, nuanced tasks, highest quality
  - Trade-off: Higher cost, slower

  *Mid-Tier (Balanced)*
  - GPT-4o-mini, Claude 3.5 Haiku, Gemini 1.5 Flash
  - Best for: Most production use cases
  - Trade-off: Good quality/cost balance

  *Efficient (Cost-Optimized)*
  - GPT-3.5-turbo, Gemini Flash 8B
  - Best for: Simple tasks, high volume
  - Trade-off: Lower capability

  *Open Source (Self-Hosted)*
  - Llama 3, Mistral, Mixtral
  - Best for: Privacy, customization, no API costs
  - Trade-off: Infrastructure overhead

  **For Each Candidate:**
  - Capability fit score
  - Cost estimate
  - Latency profile
  - API reliability
  - Vendor considerations

  <template-output section="evaluation">Document model evaluation</template-output>
</step>

<step n="4" goal="Design Evaluation Benchmark">
  <action>Create test plan for model comparison:</action>

  **Benchmark Components:**
  - Representative test cases (20-50 examples)
  - Edge cases and failure modes
  - Quality scoring rubric
  - Latency measurement
  - Cost calculation

  **Evaluation Metrics:**
  - Task success rate
  - Output quality score (1-5)
  - Average latency (p50, p95, p99)
  - Cost per successful completion
  - Error/retry rate

  **A/B Testing Plan:**
  - Sample size requirements
  - Statistical significance threshold
  - Rollout strategy

  <template-output section="benchmark">Document benchmark design</template-output>
</step>

<step n="5" goal="Make Recommendation">
  <action>Synthesize findings into recommendation:</action>

  **Decision Framework:**
  - Primary recommendation with rationale
  - Fallback option if primary fails
  - Cost projection at expected volume
  - Migration path if needs change

  **Risk Assessment:**
  - Vendor lock-in concerns
  - API stability/deprecation risk
  - Cost volatility
  - Quality degradation scenarios

  <template-output section="recommendation">Document recommendation</template-output>
</step>

<step n="6" goal="Generate Model Selection Report">
  <action>Compile comprehensive selection document:</action>
  - Executive summary with recommendation
  - Requirements analysis
  - Model comparison matrix
  - Benchmark results (if run)
  - Cost projections
  - Implementation guidance
  - Monitoring plan

  <action>Save to output file</action>
  <action>Present recommendation summary</action>
</step>

</workflow>
