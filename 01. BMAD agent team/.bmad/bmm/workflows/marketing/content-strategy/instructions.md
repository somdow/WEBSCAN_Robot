# Content Strategy Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Understand Content Goals and Audience">
  <action>Define content strategy foundation:</action>
  <ask>
    Let's build your content strategy. Tell me:
    1. What are your content marketing goals? (awareness, leads, SEO, thought leadership)
    2. Who is your target audience? (personas, pain points, interests)
    3. What topics/themes are relevant to your business?
    4. What content do you currently produce?
    5. What's your content creation capacity? (team size, budget)
    6. Who are your content competitors?
    7. What makes your brand voice unique?
  </ask>

  **Foundation Elements:**
  - Goals and KPIs
  - Audience personas
  - Brand voice
  - Resource constraints
</step>

<step n="2" goal="Develop Content Pillars">
  <action>Define core content themes:</action>

  **Content Pillar Framework:**
  - 3-5 main pillars aligned with:
    - Business expertise
    - Audience interests
    - Search opportunity
    - Competitive gaps

  **For Each Pillar:**
  - Theme description
  - Why it matters to audience
  - How it connects to your product/service
  - Subtopics to explore

  **Content Mix:**
  - Educational (how-to, guides)
  - Inspirational (stories, case studies)
  - Entertaining (culture, trends)
  - Promotional (product, offers)

  <template-output section="pillars">Document content pillars</template-output>
</step>

<step n="3" goal="Define Content Formats">
  <action>Determine optimal content types:</action>

  **Format Options:**
  - Written: Blog posts, ebooks, whitepapers
  - Visual: Infographics, images, presentations
  - Video: Tutorial, interview, demo, shorts
  - Audio: Podcast, audio articles
  - Interactive: Tools, quizzes, calculators

  **Format Selection Criteria:**
  - Audience preference
  - Topic suitability
  - Production capability
  - Distribution channel fit
  - Resource requirements

  **Content Tiers:**
  - Hero content (big, flagship pieces)
  - Hub content (regular, substantial)
  - Hygiene content (always-on, SEO)

  <template-output section="formats">Document content formats</template-output>
</step>

<step n="4" goal="Plan Distribution Strategy">
  <action>Define how content reaches audience:</action>

  **Owned Channels:**
  - Website/blog
  - Email newsletter
  - Social profiles

  **Earned Channels:**
  - Guest posting
  - PR and media
  - Influencer partnerships

  **Paid Amplification:**
  - Social ads
  - Content syndication
  - Sponsored content

  **Repurposing Strategy:**
  - One piece → multiple formats
  - Platform-specific adaptations
  - Content atomization

  <template-output section="distribution">Document distribution strategy</template-output>
</step>

<step n="5" goal="Create Editorial Calendar Framework">
  <action>Establish content production cadence:</action>

  **Calendar Elements:**
  - Publishing frequency per channel
  - Content types per week/month
  - Seasonal/timely themes
  - Campaign integration

  **Workflow:**
  - Ideation process
  - Creation timeline
  - Review/approval process
  - Publishing checklist
  - Promotion plan

  **Governance:**
  - Roles and responsibilities
  - Quality standards
  - Brand guidelines
  - Performance review cadence

  <template-output section="calendar">Document editorial calendar</template-output>
</step>

<step n="6" goal="Generate Content Strategy Document">
  <action>Compile comprehensive content strategy:</action>
  - Executive summary
  - Goals and KPIs
  - Audience personas
  - Content pillars
  - Format strategy
  - Distribution plan
  - Editorial calendar framework
  - Measurement plan
  - Resource requirements

  <action>Save to output file</action>
  <action>Present strategy summary and first month priorities</action>
</step>

</workflow>
