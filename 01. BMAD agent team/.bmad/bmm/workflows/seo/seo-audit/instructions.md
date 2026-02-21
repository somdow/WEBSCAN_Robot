# SEO Audit Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Gather Project Context">
  <action>Ask the user to provide the target for the SEO audit:</action>
  <ask>
    What would you like me to audit? Please provide:
    1. Website URL or project name
    2. Target audience/market
    3. Primary business goals
    4. Any specific concerns or focus areas?
  </ask>
  <action>If project docs exist (PRD, architecture), offer to review them for context</action>
  <action>Store all context for use throughout the audit</action>
</step>

<step n="2" goal="Technical SEO Analysis">
  <action>Analyze technical SEO foundation:</action>

  **Crawlability & Indexing:**
  - Robots.txt configuration
  - XML sitemap presence and validity
  - URL structure and cleanliness
  - Canonical tag implementation
  - Pagination handling
  - Hreflang for international (if applicable)

  **Performance & Core Web Vitals:**
  - Page load speed assessment
  - Largest Contentful Paint (LCP)
  - First Input Delay (FID) / Interaction to Next Paint (INP)
  - Cumulative Layout Shift (CLS)
  - Mobile responsiveness

  **Security & Infrastructure:**
  - HTTPS implementation
  - SSL certificate validity
  - Redirect chains and loops
  - 404 error handling
  - Server response codes

  <template-output section="technical-seo">Generate findings for Technical SEO section</template-output>
</step>

<step n="3" goal="On-Page SEO Analysis">
  <action>Evaluate on-page optimization factors:</action>

  **Title Tags:**
  - Presence and uniqueness
  - Length (50-60 characters)
  - Keyword placement
  - Brand inclusion strategy

  **Meta Descriptions:**
  - Presence and uniqueness
  - Length (150-160 characters)
  - Call-to-action inclusion
  - Keyword relevance

  **Heading Structure:**
  - H1 presence and uniqueness per page
  - Logical H2-H6 hierarchy
  - Keyword integration in headings

  **Content Elements:**
  - Image alt text optimization
  - Internal linking structure
  - External link quality
  - URL optimization

  <template-output section="on-page-seo">Generate findings for On-Page SEO section</template-output>
</step>

<step n="4" goal="Content Quality Assessment">
  <action>Evaluate content from an E-E-A-T perspective:</action>

  **Experience:**
  - First-hand experience signals
  - Original insights vs. rehashed content
  - Practical, actionable information

  **Expertise:**
  - Author credentials and bios
  - Depth of coverage
  - Technical accuracy

  **Authoritativeness:**
  - Brand reputation signals
  - Industry recognition
  - Citation by others

  **Trustworthiness:**
  - Accuracy of information
  - Transparency (contact, about, policies)
  - User reviews and testimonials

  **Content Inventory:**
  - Thin content identification
  - Duplicate content issues
  - Content gaps vs. competitors
  - Content freshness

  <template-output section="content-quality">Generate findings for Content Quality section</template-output>
</step>

<step n="5" goal="Competitive Analysis">
  <action>Analyze competitive landscape:</action>

  <action>Use web search to identify top 3-5 competitors for primary keywords</action>

  **SERP Analysis:**
  - Who ranks for target keywords?
  - What SERP features appear? (Featured snippets, PAA, local pack, etc.)
  - Content types that rank (guides, lists, tools, etc.)

  **Competitor Strengths:**
  - Content depth and quality
  - Domain authority signals
  - Unique value propositions
  - Technical advantages

  **Opportunity Gaps:**
  - Keywords competitors miss
  - Content formats not being used
  - SERP features not being targeted
  - User intent not being served

  <template-output section="competitive-analysis">Generate findings for Competitive Analysis section</template-output>
</step>

<step n="6" goal="Generate Prioritized Recommendations">
  <action>Synthesize findings into actionable recommendations:</action>

  **Priority Matrix:**
  - Critical (blocking success): Fix immediately
  - High (significant impact): Address within 2 weeks
  - Medium (incremental gains): Address within 1 month
  - Low (nice to have): Backlog for future

  **For each recommendation:**
  - What: Specific action to take
  - Why: Impact on rankings/traffic
  - How: Implementation guidance
  - Effort: Low/Medium/High

  <template-output section="recommendations">Generate prioritized recommendations</template-output>
</step>

<step n="7" goal="Compile Final Audit Report">
  <action>Review all sections for completeness</action>
  <action>Add executive summary at the top</action>
  <action>Ensure all findings have supporting evidence</action>
  <action>Save complete audit to output file</action>
  <action>Present summary to user with key findings and top 3 priorities</action>
</step>

</workflow>
