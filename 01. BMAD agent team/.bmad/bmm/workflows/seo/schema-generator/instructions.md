# Schema Generator Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Identify Content and Schema Type">
  <action>Gather information about the content needing schema:</action>
  <ask>
    Let's add structured data to your content. Tell me:
    1. What type of content is this? (article, guide, product, FAQ, recipe, event, etc.)
    2. Provide the content or URL to analyze
    3. What rich result are you targeting? (featured snippet, FAQ dropdown, how-to steps, etc.)
  </ask>

  <action>Determine appropriate schema type(s):</action>

  | Content Type | Primary Schema | Supporting Schemas |
  |--------------|----------------|-------------------|
  | How-to Guide | HowTo | Article, FAQPage, ImageObject |
  | Tutorial | HowTo | Article, VideoObject |
  | FAQ Page | FAQPage | Article, WebPage |
  | Blog Post | Article | Person (author), Organization |
  | Product Review | Review, Product | AggregateRating, Organization |
  | Recipe | Recipe | NutritionInformation, Video |
  | Event | Event | Place, Offer, Organization |
  | Local Business | LocalBusiness | OpeningHours, GeoCoordinates |
  | Video Content | VideoObject | Article, HowTo |

  <action>Recommend primary and supporting schema types</action>
</step>

<step n="2" goal="Extract Required Data">
  <action>Based on schema type, extract required properties:</action>

  **For HowTo Schema:**
  - Name (title of the how-to)
  - Description (what it accomplishes)
  - Steps (ordered list with name, text, image, url)
  - Total time (if applicable)
  - Estimated cost (if applicable)
  - Tools/materials needed
  - Images for steps

  **For Article Schema:**
  - Headline
  - Author (name, url)
  - Date published
  - Date modified
  - Publisher (name, logo)
  - Main image
  - Description

  **For FAQPage Schema:**
  - Questions (exact question text)
  - Answers (complete answer text)

  **For Product Schema:**
  - Name
  - Description
  - Image
  - Brand
  - SKU/MPN
  - Price and currency
  - Availability
  - Reviews/ratings

  <action>Document all extracted data points</action>
  <action>Flag any missing required properties</action>
</step>

<step n="3" goal="Generate JSON-LD Markup">
  <action>Create properly formatted JSON-LD:</action>

  **Structure Requirements:**
  - Use @context: "https://schema.org"
  - Use @type for schema type
  - Nest related schemas properly
  - Use @id for cross-referencing within document
  - Include all required properties
  - Add recommended properties for richer results

  **Best Practices:**
  - Use absolute URLs for all url properties
  - Format dates as ISO 8601 (YYYY-MM-DD or full datetime)
  - Use proper image dimensions (min 1200px wide for Article)
  - Include author/publisher for credibility
  - Escape special characters in text

  <template-output section="json-ld">Generate the JSON-LD markup</template-output>
</step>

<step n="4" goal="Validate Schema">
  <action>Verify schema validity:</action>

  **Validation Checks:**
  - [ ] All required properties present
  - [ ] Property values match expected types
  - [ ] URLs are absolute and valid
  - [ ] Dates are properly formatted
  - [ ] No deprecated properties used
  - [ ] Nested objects properly structured
  - [ ] JSON syntax is valid (no trailing commas, proper quotes)

  **Google Guidelines Compliance:**
  - [ ] Content matches schema claims
  - [ ] No misleading or fake markup
  - [ ] Schema represents visible page content
  - [ ] Not used for content not on page

  <action>Provide testing instructions:</action>
  - Google Rich Results Test: https://search.google.com/test/rich-results
  - Schema.org Validator: https://validator.schema.org/

  <template-output section="validation">Document validation results</template-output>
</step>

<step n="5" goal="Generate Implementation Guide">
  <action>Provide implementation instructions:</action>

  **Placement Options:**
  1. In <head> section (recommended)
  2. In <body> before closing tag
  3. Via Google Tag Manager
  4. Via WordPress plugin (Yoast, RankMath)

  **WordPress/Yoast Implementation:**
  - Custom schema via Yoast SEO settings
  - Add to theme's header.php
  - Use custom field for per-page schema

  **Code Snippet:**
  ```html
  <script type="application/ld+json">
  {schema_json}
  </script>
  ```

  <action>Provide copy-paste ready code</action>
  <template-output section="implementation">Generate implementation guide</template-output>
</step>

<step n="6" goal="Compile Final Output">
  <action>Create complete schema documentation:</action>
  - Schema type summary
  - Complete JSON-LD code (copy-paste ready)
  - Implementation instructions
  - Testing links
  - Maintenance notes (when to update)

  <action>Save to output file</action>
  <action>Present schema with key implementation notes</action>
</step>

</workflow>
