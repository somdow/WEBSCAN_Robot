# Content Optimization Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Gather Content and Context">
  <action>Request the content to optimize:</action>
  <ask>
    Let's optimize your content for search. Please provide:
    1. The content to review (paste text, file path, or URL)
    2. Target keyword/keyphrase (if known)
    3. Target audience
    4. What action do you want readers to take?
    5. Any competing content you want to outrank?
  </ask>
  <action>If target keyword not provided, suggest one based on content analysis</action>
  <action>Load and analyze the content</action>
</step>

<step n="2" goal="SERP Analysis">
  <action>Analyze what's currently ranking for the target keyword:</action>

  <action>Use web search to find top 5 results for the target keyword</action>

  **Document for each competitor:**
  - Title and meta description approach
  - Content length (estimate)
  - Content structure (headings, lists, tables)
  - Unique angles or value propositions
  - SERP features they capture
  - Gaps or weaknesses to exploit

  <template-output section="serp-analysis">Document SERP findings</template-output>
</step>

<step n="3" goal="Title and Meta Optimization">
  <action>Evaluate and optimize title tag:</action>

  **Title Tag Checklist:**
  - [ ] Contains primary keyword (ideally near front)
  - [ ] 50-60 characters (won't truncate)
  - [ ] Compelling and click-worthy
  - [ ] Unique value proposition clear
  - [ ] Matches search intent

  <action>Provide current title assessment</action>
  <action>Generate 3 optimized title alternatives</action>

  **Meta Description Checklist:**
  - [ ] Contains primary keyword naturally
  - [ ] 150-160 characters
  - [ ] Includes call-to-action
  - [ ] Summarizes value proposition
  - [ ] Differentiates from competitors

  <action>Provide current meta assessment</action>
  <action>Generate 3 optimized meta alternatives</action>

  <template-output section="title-meta">Document title and meta recommendations</template-output>
</step>

<step n="4" goal="Content Structure Analysis">
  <action>Evaluate content structure for SEO:</action>

  **Heading Hierarchy:**
  - H1: One per page, contains primary keyword
  - H2s: Main sections, contain secondary keywords
  - H3s: Subsections, support scanability
  - Logical flow from general to specific

  **Content Organization:**
  - Does intro hook the reader immediately?
  - Is content scannable (short paragraphs, bullets, tables)?
  - Are key points easy to find?
  - Does structure match search intent?
  - Is there a clear conclusion/CTA?

  **Featured Snippet Optimization:**
  - Could any section be restructured for Position 0?
  - Are there opportunities for list snippets?
  - Table snippet opportunities?
  - Definition/answer snippets?

  <action>Provide specific structural recommendations</action>
  <template-output section="structure">Document structure recommendations</template-output>
</step>

<step n="5" goal="Keyword Optimization">
  <action>Analyze keyword usage and optimization:</action>

  **Primary Keyword Placement:**
  - [ ] In title tag
  - [ ] In H1
  - [ ] In first 100 words
  - [ ] In at least one H2
  - [ ] In meta description
  - [ ] In image alt text
  - [ ] In URL (if applicable)
  - [ ] Natural density throughout (not stuffed)

  **Secondary/LSI Keywords:**
  - Identify related terms that should be included
  - Check for semantic coverage of topic
  - Suggest naturally fitting additions

  **Keyword Issues:**
  - Keyword stuffing detected?
  - Missing important variations?
  - Cannibalization with other content?

  <template-output section="keywords">Document keyword optimization recommendations</template-output>
</step>

<step n="6" goal="E-E-A-T Enhancement">
  <action>Evaluate and enhance E-E-A-T signals:</action>

  **Experience:**
  - Does content show first-hand experience?
  - Are there personal insights or anecdotes?
  - Suggestions to add experience signals

  **Expertise:**
  - Is depth of knowledge demonstrated?
  - Are claims supported by evidence?
  - Technical accuracy verified?
  - Suggestions to strengthen expertise

  **Authoritativeness:**
  - Is author credentialed/introduced?
  - Are authoritative sources cited?
  - Suggestions to build authority

  **Trustworthiness:**
  - Is information accurate and current?
  - Are sources transparent?
  - Contact/about information present?
  - Suggestions to increase trust

  <template-output section="eeat">Document E-E-A-T recommendations</template-output>
</step>

<step n="7" goal="Technical Content Elements">
  <action>Review technical SEO elements within content:</action>

  **Images:**
  - Alt text present and optimized?
  - File names descriptive?
  - Images compressed for speed?
  - Relevant images support content?

  **Links:**
  - Internal links to related content?
  - External links to authoritative sources?
  - Broken links?
  - Anchor text optimized?

  **Readability:**
  - Sentence length appropriate?
  - Reading level matches audience?
  - Jargon explained where needed?

  **Schema Opportunity:**
  - What schema types apply? (Article, HowTo, FAQ, etc.)
  - Recommend schema implementation

  <template-output section="technical">Document technical recommendations</template-output>
</step>

<step n="8" goal="Generate Optimization Report">
  <action>Compile all findings into actionable report:</action>
  - SEO score assessment (before/potential after)
  - Prioritized list of changes
  - Specific copy suggestions where applicable
  - Quick wins vs. deeper rewrites

  <action>Save report to output file</action>
  <action>Present top 5 highest-impact changes to make immediately</action>
</step>

</workflow>
