# Marketing Audit Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Understand Current Marketing Landscape">
  <action>Gather comprehensive marketing context:</action>
  <ask>
    Let's audit your marketing. Tell me:
    1. What are your current marketing channels? (paid, organic, email, social, etc.)
    2. Who is your target audience? (demographics, psychographics)
    3. What's your monthly marketing budget?
    4. What are your main marketing goals? (awareness, leads, conversions)
    5. Who are your top 3 competitors?
    6. What's your current marketing team structure?
    7. What tools/platforms do you use?
  </ask>

  **Discovery Areas:**
  - Channel mix
  - Budget allocation
  - Team capabilities
  - Tech stack
</step>

<step n="2" goal="Audit Brand and Messaging">
  <action>Evaluate brand consistency and messaging effectiveness:</action>

  **Brand Elements:**
  - Logo and visual identity
  - Brand voice and tone
  - Key messaging pillars
  - Value proposition clarity

  **Messaging Audit:**
  - Homepage headline
  - Product descriptions
  - Ad copy consistency
  - Email subject lines
  - Social media voice

  **Consistency Check:**
  - Cross-channel alignment
  - Brand guideline adherence
  - Competitive differentiation

  <template-output section="brand">Document brand and messaging findings</template-output>
</step>

<step n="3" goal="Evaluate Channel Performance">
  <action>Assess each marketing channel's effectiveness:</action>

  **Paid Channels:**
  - Google Ads (search, display, shopping)
  - Social ads (Meta, LinkedIn, TikTok)
  - Programmatic/display
  - Retargeting

  **Organic Channels:**
  - SEO performance
  - Social media organic
  - Content marketing
  - PR/earned media

  **Owned Channels:**
  - Email marketing
  - Website/landing pages
  - Blog
  - Community

  **For Each Channel:**
  - Budget/spend
  - Key metrics (CAC, ROAS, engagement)
  - What's working
  - What's not working

  <template-output section="channels">Document channel performance</template-output>
</step>

<step n="4" goal="Analyze Customer Journey">
  <action>Map and evaluate the marketing funnel:</action>

  **Funnel Stages:**
  - Awareness: How do people discover you?
  - Consideration: What content nurtures them?
  - Conversion: What drives purchase/signup?
  - Retention: How do you re-engage?
  - Advocacy: How do you drive referrals?

  **Journey Analysis:**
  - Touch points mapped
  - Drop-off points identified
  - Time to conversion
  - Multi-touch attribution

  <template-output section="journey">Document customer journey analysis</template-output>
</step>

<step n="5" goal="Competitive Analysis">
  <action>Analyze competitor marketing strategies:</action>

  **For Each Competitor:**
  - Channel presence
  - Messaging/positioning
  - Content strategy
  - Ad spend estimates (tools like SimilarWeb, SEMrush)
  - Share of voice

  **Opportunities:**
  - Gaps competitors aren't addressing
  - Channels they're not using
  - Messaging angles to differentiate

  <template-output section="competitive">Document competitive analysis</template-output>
</step>

<step n="6" goal="Generate Marketing Audit Report">
  <action>Compile comprehensive marketing audit:</action>
  - Executive summary with marketing health score
  - Brand and messaging assessment
  - Channel-by-channel analysis
  - Customer journey mapping
  - Competitive landscape
  - Gap analysis and opportunities
  - Prioritized recommendations
  - Quick wins vs strategic initiatives

  <action>Save to output file</action>
  <action>Present marketing health score and top 5 recommendations</action>
</step>

</workflow>
