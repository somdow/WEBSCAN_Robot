# Dashboard Design Specification

**Date:** {{date}}
**Dashboard Name:** {{dashboard_name}}
**Author:** Dana (Data)

---

## Executive Summary

{{executive_summary}}

**Primary Audience:** {{primary_audience}}
**View Frequency:** {{view_frequency}}
**Platform:** {{platform}}

---

## 1. Purpose and Audience

### Dashboard Objective

{{dashboard_objective}}

### Target Audience

| Audience | Role | Key Questions | Frequency |
|----------|------|---------------|-----------|
| {{aud_1}} | {{role_1}} | {{questions_1}} | {{freq_1}} |
| {{aud_2}} | {{role_2}} | {{questions_2}} | {{freq_2}} |

### Key Decisions Supported

{{decisions_supported}}

### Viewing Context

- **Primary Device:** {{primary_device}}
- **Environment:** {{environment}}
- **Session Length:** {{session_length}}

---

## 2. Metrics Specification

### Primary KPIs

| Metric | Definition | Target | Source | Refresh |
|--------|------------|--------|--------|---------|
| {{kpi_1}} | {{def_1}} | {{target_1}} | {{source_1}} | {{refresh_1}} |
| {{kpi_2}} | {{def_2}} | {{target_2}} | {{source_2}} | {{refresh_2}} |
| {{kpi_3}} | {{def_3}} | {{target_3}} | {{source_3}} | {{refresh_3}} |

### Secondary Metrics

| Metric | Definition | Context | Source |
|--------|------------|---------|--------|
| {{sec_1}} | {{sdef_1}} | {{ctx_1}} | {{ssrc_1}} |
| {{sec_2}} | {{sdef_2}} | {{ctx_2}} | {{ssrc_2}} |
| {{sec_3}} | {{sdef_3}} | {{ctx_3}} | {{ssrc_3}} |
| {{sec_4}} | {{sdef_4}} | {{ctx_4}} | {{ssrc_4}} |

### Metric Calculations

{{metric_calculations}}

---

## 3. Visualization Design

### Chart Specifications

| Metric | Chart Type | Dimensions | Comparison |
|--------|-----------|------------|------------|
| {{viz_metric_1}} | {{chart_1}} | {{dim_1}} | {{comp_1}} |
| {{viz_metric_2}} | {{chart_2}} | {{dim_2}} | {{comp_2}} |
| {{viz_metric_3}} | {{chart_3}} | {{dim_3}} | {{comp_3}} |
| {{viz_metric_4}} | {{chart_4}} | {{dim_4}} | {{comp_4}} |

### Visualization Details

#### {{viz_1_name}}

- **Type:** {{viz_1_type}}
- **Data:** {{viz_1_data}}
- **Axes:** {{viz_1_axes}}
- **Colors:** {{viz_1_colors}}
- **Annotations:** {{viz_1_annotations}}

#### {{viz_2_name}}

- **Type:** {{viz_2_type}}
- **Data:** {{viz_2_data}}
- **Axes:** {{viz_2_axes}}
- **Colors:** {{viz_2_colors}}
- **Annotations:** {{viz_2_annotations}}

### Color Palette

| Usage | Color | Hex |
|-------|-------|-----|
| Primary | {{color_1}} | {{hex_1}} |
| Positive | {{color_2}} | {{hex_2}} |
| Negative | {{color_3}} | {{hex_3}} |
| Neutral | {{color_4}} | {{hex_4}} |

---

## 4. Layout Design

### Wireframe

```
┌────────────────────────────────────────────────────────┐
│  HEADER: Dashboard Title | Filters | Date Range        │
├──────────────────┬──────────────────┬──────────────────┤
│                  │                  │                  │
│    KPI Card 1    │    KPI Card 2    │    KPI Card 3    │
│                  │                  │                  │
├──────────────────┴──────────────────┴──────────────────┤
│                                                        │
│              Primary Trend Chart                       │
│                                                        │
├──────────────────────────┬─────────────────────────────┤
│                          │                             │
│    Comparison Chart      │     Breakdown Chart         │
│                          │                             │
├──────────────────────────┴─────────────────────────────┤
│                                                        │
│              Detail Table (if needed)                  │
│                                                        │
└────────────────────────────────────────────────────────┘
```

### Component Specifications

| Component | Position | Size | Priority |
|-----------|----------|------|----------|
| {{comp_1}} | {{pos_1}} | {{size_1}} | {{pri_1}} |
| {{comp_2}} | {{pos_2}} | {{size_2}} | {{pri_2}} |
| {{comp_3}} | {{pos_3}} | {{size_3}} | {{pri_3}} |
| {{comp_4}} | {{pos_4}} | {{size_4}} | {{pri_4}} |

### Responsive Behavior

{{responsive_behavior}}

---

## 5. Interactivity

### Filters

| Filter | Type | Default | Options |
|--------|------|---------|---------|
| Date Range | {{date_type}} | {{date_default}} | {{date_options}} |
| {{filter_1}} | {{ftype_1}} | {{fdefault_1}} | {{foptions_1}} |
| {{filter_2}} | {{ftype_2}} | {{fdefault_2}} | {{foptions_2}} |

### Drill-Down Paths

{{drill_down_paths}}

### Conditional Formatting

| Condition | Visual Treatment | Threshold |
|-----------|-----------------|-----------|
| {{cond_1}} | {{treatment_1}} | {{thresh_1}} |
| {{cond_2}} | {{treatment_2}} | {{thresh_2}} |
| {{cond_3}} | {{treatment_3}} | {{thresh_3}} |

### Tooltips

{{tooltip_specifications}}

---

## 6. Data Requirements

### Data Sources

| Source | Tables/APIs | Update Frequency | Owner |
|--------|-------------|------------------|-------|
| {{dsource_1}} | {{tables_1}} | {{dupdate_1}} | {{downer_1}} |
| {{dsource_2}} | {{tables_2}} | {{dupdate_2}} | {{downer_2}} |

### Data Transformations

{{data_transformations}}

### Performance Considerations

{{performance_considerations}}

---

## 7. Implementation Notes

### Tool Recommendation

{{tool_recommendation}}

### Development Steps

{{development_steps}}

### Testing Checklist

{{testing_checklist}}

### Maintenance Plan

{{maintenance_plan}}

---

*Generated by Dana - BMAD Data Analyst*
