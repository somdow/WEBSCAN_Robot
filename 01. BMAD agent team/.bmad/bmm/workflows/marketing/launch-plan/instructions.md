# Launch Plan Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Define Launch Scope">
  <action>Understand what you're launching and why:</action>
  <ask>
    Let's plan your launch. Tell me:
    1. What are you launching? (new product, feature, company, campaign)
    2. What's the target launch date?
    3. Who is the primary target audience?
    4. What problem does this solve for them?
    5. What makes this unique/different from alternatives?
    6. What does success look like? (specific metrics)
    7. What's your launch budget?
    8. What are the dependencies/risks?
  </ask>

  **Launch Types:**
  - Soft launch (limited audience, test)
  - Hard launch (full public release)
  - Phased launch (staged rollout)

  <template-output section="scope">Document launch scope</template-output>
</step>

<step n="2" goal="Develop Positioning and Messaging">
  <action>Create launch messaging framework:</action>

  **Positioning Statement:**
  For [target audience] who [need/problem],
  [product] is a [category]
  that [key benefit].
  Unlike [alternative],
  we [key differentiator].

  **Messaging Hierarchy:**
  - Headline (7 words max)
  - Subheadline (benefit statement)
  - Supporting points (3 pillars)
  - Proof points (evidence)

  **Audience-Specific Messaging:**
  - By persona
  - By use case
  - By objection

  <template-output section="messaging">Document positioning and messaging</template-output>
</step>

<step n="3" goal="Plan Launch Channels">
  <action>Define channel strategy:</action>

  **Owned Channels:**
  - Website/landing page
  - Email list
  - Blog
  - Social accounts
  - In-product

  **Earned Channels:**
  - Press/media outreach
  - Influencer partnerships
  - Community/word of mouth
  - Product Hunt, etc.

  **Paid Channels:**
  - Paid social
  - Search ads
  - Display/retargeting
  - Sponsorships

  **For Each Channel:**
  - Role in launch
  - Content needed
  - Timing
  - Owner

  <template-output section="channels">Document channel plan</template-output>
</step>

<step n="4" goal="Create Launch Timeline">
  <action>Build detailed launch schedule:</action>

  **Pre-Launch (T-30 to T-7):**
  - Internal alignment
  - Asset creation
  - Partner coordination
  - Tech readiness
  - Beta/early access

  **Launch Week (T-7 to T+0):**
  - Final prep
  - Embargo coordination
  - Day-of execution
  - Real-time monitoring

  **Post-Launch (T+1 to T+30):**
  - Follow-up campaigns
  - Performance monitoring
  - Iteration
  - Retrospective

  <template-output section="timeline">Document launch timeline</template-output>
</step>

<step n="5" goal="Define Success Metrics">
  <action>Establish launch KPIs:</action>

  **Awareness Metrics:**
  - Impressions
  - Media mentions
  - Social reach
  - Website traffic

  **Engagement Metrics:**
  - Click-through rates
  - Time on page
  - Social engagement
  - Email opens

  **Conversion Metrics:**
  - Sign-ups
  - Trial starts
  - Purchases
  - Revenue

  **Set Targets:**
  - Day 1 goals
  - Week 1 goals
  - Month 1 goals

  <template-output section="metrics">Document success metrics</template-output>
</step>

<step n="6" goal="Generate Launch Plan Document">
  <action>Compile comprehensive launch plan:</action>
  - Executive summary
  - Launch scope and goals
  - Positioning and messaging
  - Channel strategy
  - Detailed timeline
  - Asset checklist
  - Success metrics
  - Risk mitigation
  - Team responsibilities
  - Budget allocation

  <action>Save to output file</action>
  <action>Present launch plan summary and critical path items</action>
</step>

</workflow>
