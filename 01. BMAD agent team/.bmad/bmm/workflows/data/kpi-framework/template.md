# KPI Framework Document

**Date:** {{date}}
**Organization:** {{organization_name}}
**Author:** Dana (Data)

---

## Executive Summary

{{executive_summary}}

**Business Model:** {{business_model}}
**Primary Objective:** {{primary_objective}}

---

## 1. North Star Metric

### Definition

**Metric:** {{north_star_metric}}
**Formula:** {{north_star_formula}}

### Why This Metric?

{{north_star_rationale}}

### Current State

| Measure | Value |
|---------|-------|
| Current Baseline | {{ns_baseline}} |
| 30-Day Target | {{ns_30day}} |
| 90-Day Target | {{ns_90day}} |
| Annual Target | {{ns_annual}} |

### Drivers

{{north_star_drivers}}

---

## 2. Metrics Hierarchy

### Visual Framework

```
                    ┌─────────────────┐
                    │   NORTH STAR    │
                    │ {{north_star}}  │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
   ┌────▼────┐         ┌────▼────┐         ┌────▼────┐
   │  KPI 1  │         │  KPI 2  │         │  KPI 3  │
   │{{kpi_1}}│         │{{kpi_2}}│         │{{kpi_3}}│
   └────┬────┘         └────┬────┘         └────┬────┘
        │                   │                   │
   ┌────▼────┐         ┌────▼────┐         ┌────▼────┐
   │Tactical │         │Tactical │         │Tactical │
   │ Metrics │         │ Metrics │         │ Metrics │
   └─────────┘         └─────────┘         └─────────┘
```

### Level 2: Strategic KPIs

| KPI | Definition | Owner | Current | Target |
|-----|------------|-------|---------|--------|
| {{kpi_1_name}} | {{kpi_1_def}} | {{kpi_1_owner}} | {{kpi_1_current}} | {{kpi_1_target}} |
| {{kpi_2_name}} | {{kpi_2_def}} | {{kpi_2_owner}} | {{kpi_2_current}} | {{kpi_2_target}} |
| {{kpi_3_name}} | {{kpi_3_def}} | {{kpi_3_owner}} | {{kpi_3_current}} | {{kpi_3_target}} |
| {{kpi_4_name}} | {{kpi_4_def}} | {{kpi_4_owner}} | {{kpi_4_current}} | {{kpi_4_target}} |
| {{kpi_5_name}} | {{kpi_5_def}} | {{kpi_5_owner}} | {{kpi_5_current}} | {{kpi_5_target}} |

---

## 3. Detailed Metric Definitions

### {{kpi_1_name}}

**Definition:** {{kpi_1_detailed_def}}
**Formula:** `{{kpi_1_formula}}`
**Data Source:** {{kpi_1_source}}

**Tactical Metrics:**

| Metric | Formula | Frequency | Owner |
|--------|---------|-----------|-------|
| {{tac_1_1}} | {{form_1_1}} | {{freq_1_1}} | {{own_1_1}} |
| {{tac_1_2}} | {{form_1_2}} | {{freq_1_2}} | {{own_1_2}} |
| {{tac_1_3}} | {{form_1_3}} | {{freq_1_3}} | {{own_1_3}} |

### {{kpi_2_name}}

**Definition:** {{kpi_2_detailed_def}}
**Formula:** `{{kpi_2_formula}}`
**Data Source:** {{kpi_2_source}}

**Tactical Metrics:**

| Metric | Formula | Frequency | Owner |
|--------|---------|-----------|-------|
| {{tac_2_1}} | {{form_2_1}} | {{freq_2_1}} | {{own_2_1}} |
| {{tac_2_2}} | {{form_2_2}} | {{freq_2_2}} | {{own_2_2}} |
| {{tac_2_3}} | {{form_2_3}} | {{freq_2_3}} | {{own_2_3}} |

### {{kpi_3_name}}

**Definition:** {{kpi_3_detailed_def}}
**Formula:** `{{kpi_3_formula}}`
**Data Source:** {{kpi_3_source}}

**Tactical Metrics:**

| Metric | Formula | Frequency | Owner |
|--------|---------|-----------|-------|
| {{tac_3_1}} | {{form_3_1}} | {{freq_3_1}} | {{own_3_1}} |
| {{tac_3_2}} | {{form_3_2}} | {{freq_3_2}} | {{own_3_2}} |
| {{tac_3_3}} | {{form_3_3}} | {{freq_3_3}} | {{own_3_3}} |

---

## 4. Targets and Benchmarks

### Target Summary

| Metric | Baseline | 30-Day | 90-Day | Annual | Benchmark |
|--------|----------|--------|--------|--------|-----------|
| {{metric_1}} | {{base_1}} | {{t30_1}} | {{t90_1}} | {{ta_1}} | {{bench_1}} |
| {{metric_2}} | {{base_2}} | {{t30_2}} | {{t90_2}} | {{ta_2}} | {{bench_2}} |
| {{metric_3}} | {{base_3}} | {{t30_3}} | {{t90_3}} | {{ta_3}} | {{bench_3}} |
| {{metric_4}} | {{base_4}} | {{t30_4}} | {{t90_4}} | {{ta_4}} | {{bench_4}} |
| {{metric_5}} | {{base_5}} | {{t30_5}} | {{t90_5}} | {{ta_5}} | {{bench_5}} |

### Target Rationale

{{target_rationale}}

### Industry Benchmarks

{{industry_benchmarks}}

---

## 5. Measurement Strategy

### Data Sources

| Metric | Source | Update Frequency | Data Quality |
|--------|--------|------------------|--------------|
| {{ds_metric_1}} | {{ds_source_1}} | {{ds_freq_1}} | {{ds_quality_1}} |
| {{ds_metric_2}} | {{ds_source_2}} | {{ds_freq_2}} | {{ds_quality_2}} |
| {{ds_metric_3}} | {{ds_source_3}} | {{ds_freq_3}} | {{ds_quality_3}} |

### Segmentation Strategy

{{segmentation_strategy}}

### Alerting Rules

| Metric | Warning Threshold | Critical Threshold | Owner | Response |
|--------|-------------------|-------------------|-------|----------|
| {{alert_1}} | {{warn_1}} | {{crit_1}} | {{alert_own_1}} | {{resp_1}} |
| {{alert_2}} | {{warn_2}} | {{crit_2}} | {{alert_own_2}} | {{resp_2}} |

---

## 6. Review Cadence

### Meeting Rhythm

| Review | Frequency | Attendees | Metrics Reviewed |
|--------|-----------|-----------|------------------|
| Daily Standup | Daily | Team | Operational |
| Weekly Review | Weekly | Team Lead | Tactical |
| Monthly Business Review | Monthly | Leadership | Strategic |
| Quarterly Planning | Quarterly | Executives | North Star + KPIs |

### Dashboard Access

{{dashboard_access}}

---

## 7. Governance

### Metric Ownership

{{metric_ownership}}

### Change Management

{{change_management}}

### Data Quality Responsibility

{{data_quality_responsibility}}

---

## 8. Implementation Roadmap

### Phase 1: Foundation (Week 1-2)

{{phase1_implementation}}

### Phase 2: Tracking (Week 3-4)

{{phase2_implementation}}

### Phase 3: Dashboards (Week 5-6)

{{phase3_implementation}}

### Phase 4: Optimization (Ongoing)

{{phase4_implementation}}

---

## Appendix: Glossary

{{glossary}}

---

*Generated by Dana - BMAD Data Analyst*
