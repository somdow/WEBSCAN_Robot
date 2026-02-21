# Schema Generator Validation Checklist

## Schema Selection

- [ ] Appropriate schema type selected for content
- [ ] Supporting schemas identified if applicable
- [ ] Schema matches actual page content

## Required Properties

### All Schema Types
- [ ] @context is "https://schema.org"
- [ ] @type is correctly specified
- [ ] All required properties for type are present

### Article Schema
- [ ] headline present
- [ ] author present with name
- [ ] datePublished in ISO 8601 format
- [ ] publisher with name and logo
- [ ] image present (min 1200px wide)

### HowTo Schema
- [ ] name present
- [ ] step array with ordered HowToStep objects
- [ ] Each step has text property
- [ ] totalTime in ISO 8601 duration format (if claimed)

### FAQPage Schema
- [ ] mainEntity array present
- [ ] Each FAQ has @type Question
- [ ] Each Question has name and acceptedAnswer
- [ ] Each acceptedAnswer has text

### Product Schema
- [ ] name present
- [ ] image present
- [ ] offers with price, priceCurrency, availability

## Technical Validation

- [ ] Valid JSON syntax (parseable)
- [ ] No trailing commas
- [ ] Proper quote escaping in strings
- [ ] All URLs are absolute
- [ ] Dates in ISO 8601 format
- [ ] No deprecated properties used

## Google Guidelines Compliance

- [ ] Schema represents visible page content
- [ ] No misleading or exaggerated claims
- [ ] Not marking up content hidden from users
- [ ] Reviews are genuine (if using Review schema)
- [ ] Prices accurate (if using Product/Offer)

## Implementation

- [ ] Copy-paste ready code provided
- [ ] Placement instructions clear
- [ ] Testing links provided
- [ ] Maintenance notes included

## Quality

- [ ] All extracted data is accurate
- [ ] Schema enhances search appearance
- [ ] No unnecessary/redundant markup
- [ ] Documentation is clear and actionable
