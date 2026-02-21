# Keyword Research Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Define Research Scope">
  <action>Gather context for keyword research:</action>
  <ask>
    Let's find the right keywords. Tell me:
    1. What niche/topic/industry are we targeting?
    2. What products/services/content will you offer?
    3. Who is your target audience?
    4. Any seed keywords you already have in mind?
    5. Are you targeting local, national, or international?
  </ask>
  <action>If project docs exist (PRD, product brief), offer to extract context from them</action>
  <action>Document the scope parameters for the research</action>
</step>

<step n="2" goal="Seed Keyword Expansion">
  <action>Start with seed keywords and expand:</action>

  **Expansion Methods:**
  - Synonyms and variations
  - Long-tail extensions (what, how, why, best, vs, etc.)
  - Question-based keywords (how to, what is, why does)
  - Comparison keywords (X vs Y, X alternative)
  - Modifier keywords (best, top, cheap, free, near me)
  - Problem-based keywords (fix, solve, troubleshoot)
  - Year-based keywords (2024, 2025)

  **Use web search to:**
  - Check Google autocomplete suggestions
  - Review "People Also Ask" boxes
  - Analyze related searches at bottom of SERPs
  - Check competitor content for keyword ideas

  <action>Generate initial keyword list (aim for 50-100 candidates)</action>
  <template-output section="seed-expansion">Document expanded keyword list</template-output>
</step>

<step n="3" goal="Search Intent Classification">
  <action>Classify each keyword by search intent:</action>

  **Intent Types:**
  | Intent | Description | Example | Content Type |
  |--------|-------------|---------|--------------|
  | Informational | Learning/researching | "how to mine in star citizen" | Guide, tutorial, explainer |
  | Navigational | Finding specific site/page | "star citizen download" | Landing page |
  | Commercial | Comparing options | "best mining ship star citizen" | Comparison, review |
  | Transactional | Ready to act | "buy aurora star citizen" | Product page, CTA |

  <action>Tag each keyword with primary intent</action>
  <action>Group keywords by intent for content planning</action>
  <template-output section="intent-mapping">Document intent classifications</template-output>
</step>

<step n="4" goal="Competition & Difficulty Analysis">
  <action>Assess competition for priority keywords:</action>

  **For each high-potential keyword:**
  - Search and analyze top 10 results
  - Note content types ranking (articles, videos, tools, forums)
  - Assess content depth and quality
  - Identify domain authority patterns
  - Check for SERP features (featured snippets, PAA, video carousels)

  **Difficulty Assessment:**
  | Difficulty | Indicators |
  |------------|------------|
  | Low | Forums, thin content, old content ranking |
  | Medium | Mix of authority sites and smaller sites |
  | High | All major authority sites dominating |
  | Very High | Major brands + SERP features locked |

  <action>Assign difficulty score (Low/Medium/High/Very High) to each keyword</action>
  <template-output section="competition">Document competition analysis</template-output>
</step>

<step n="5" goal="Opportunity Scoring & Prioritization">
  <action>Score and prioritize keywords:</action>

  **Scoring Factors:**
  - Relevance to business (1-5)
  - Estimated search volume (use web research for trends)
  - Competition difficulty (inverse score)
  - Conversion potential (how close to action?)
  - Content feasibility (can we create best-in-class content?)

  **Priority Formula:**
  Priority = (Relevance × Volume Potential × Conversion) / Difficulty

  **Categorize into:**
  - Quick Wins: High relevance, low difficulty
  - Big Bets: High volume, high difficulty (long-term)
  - Niche Gems: Low volume, low difficulty, high conversion
  - Avoid: Low relevance or impossible difficulty

  <template-output section="prioritization">Document prioritized keyword list</template-output>
</step>

<step n="6" goal="Content Cluster Planning">
  <action>Organize keywords into topic clusters:</action>

  **Cluster Structure:**
  - Pillar Page: Broad topic (high competition, comprehensive)
  - Cluster Content: Specific subtopics (lower competition, detailed)
  - Internal Links: Connect cluster to pillar

  **For each cluster:**
  - Identify pillar keyword
  - Group supporting keywords
  - Map content types needed
  - Suggest internal linking strategy

  <template-output section="clusters">Document content clusters</template-output>
</step>

<step n="7" goal="Generate Keyword Strategy Document">
  <action>Compile final keyword strategy:</action>
  - Executive summary with top opportunities
  - Full prioritized keyword list
  - Content cluster map
  - Recommended content calendar priorities
  - Quick win targets for immediate action

  <action>Save to output file</action>
  <action>Present top 10 priority keywords with recommended next steps</action>
</step>

</workflow>
