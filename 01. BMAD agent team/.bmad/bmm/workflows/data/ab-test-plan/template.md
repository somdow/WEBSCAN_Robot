# A/B Test Plan

**Date:** {{date}}
**Test Name:** {{test_name}}
**Author:** Dana (Data)

---

## Executive Summary

{{executive_summary}}

**Test Type:** {{test_type}}
**Primary Metric:** {{primary_metric}}
**Expected Duration:** {{expected_duration}}
**Traffic Allocation:** {{traffic_allocation}}

---

## 1. Background and Rationale

### Business Context

{{business_context}}

### Problem Statement

{{problem_statement}}

### Proposed Solution

{{proposed_solution}}

---

## 2. Hypothesis

### Primary Hypothesis

**If we** {{intervention}},
**then** {{primary_metric}} **will** {{expected_change}}
**by** {{expected_magnitude}},
**because** {{rationale}}.

### Null Hypothesis (H0)

{{null_hypothesis}}

### Alternative Hypothesis (H1)

{{alternative_hypothesis}}

### Hypothesis Type

- [ ] One-tailed (directional)
- [ ] Two-tailed (non-directional)

---

## 3. Metrics

### Primary Metric

| Attribute | Value |
|-----------|-------|
| Metric Name | {{primary_metric_name}} |
| Definition | {{primary_metric_def}} |
| Current Baseline | {{baseline_value}} |
| Minimum Detectable Effect | {{mde}} |
| Target | {{target_value}} |

### Secondary Metrics

| Metric | Definition | Baseline | Expected Change |
|--------|------------|----------|-----------------|
| {{sec_metric_1}} | {{sec_def_1}} | {{sec_base_1}} | {{sec_change_1}} |
| {{sec_metric_2}} | {{sec_def_2}} | {{sec_base_2}} | {{sec_change_2}} |
| {{sec_metric_3}} | {{sec_def_3}} | {{sec_base_3}} | {{sec_change_3}} |

### Guardrail Metrics

| Metric | Threshold | Action if Violated |
|--------|-----------|-------------------|
| {{guard_1}} | {{thresh_1}} | {{action_1}} |
| {{guard_2}} | {{thresh_2}} | {{action_2}} |

---

## 4. Sample Size Calculation

### Parameters

| Parameter | Value |
|-----------|-------|
| Baseline Conversion Rate | {{baseline_rate}} |
| Minimum Detectable Effect (relative) | {{mde_relative}} |
| Statistical Significance (α) | {{alpha}} |
| Statistical Power (1-β) | {{power}} |
| Test Type | {{test_type_stat}} |

### Calculation Results

| Metric | Value |
|--------|-------|
| Sample Size per Variant | {{sample_per_variant}} |
| Total Sample Size | {{total_sample}} |
| Daily Traffic | {{daily_traffic}} |
| Minimum Duration | {{min_duration}} |
| Recommended Duration | {{rec_duration}} |

### Duration Considerations

{{duration_considerations}}

---

## 5. Test Design

### Variants

| Variant | Name | Description | Allocation |
|---------|------|-------------|------------|
| Control | {{control_name}} | {{control_desc}} | {{control_alloc}}% |
| Treatment | {{treatment_name}} | {{treatment_desc}} | {{treatment_alloc}}% |

### Variant Details

#### Control (A)

{{control_details}}

#### Treatment (B)

{{treatment_details}}

### Randomization

| Attribute | Value |
|-----------|-------|
| Randomization Unit | {{random_unit}} |
| Assignment Method | {{assignment_method}} |
| Persistence | {{persistence}} |

### Targeting and Segmentation

**Included:**
{{included_segments}}

**Excluded:**
{{excluded_segments}}

### Technical Implementation

{{technical_implementation}}

---

## 6. Analysis Plan

### Statistical Approach

| Element | Value |
|---------|-------|
| Statistical Test | {{stat_test}} |
| Significance Level | {{sig_level}} |
| Confidence Interval | {{ci_level}} |
| Multiple Comparison Correction | {{correction_method}} |

### Analysis Schedule

| Checkpoint | Date | Action |
|------------|------|--------|
| Test Start | {{start_date}} | Begin data collection |
| Midpoint Check | {{mid_date}} | Guardrail review only |
| Final Analysis | {{end_date}} | Full analysis and decision |

### Peeking Policy

{{peeking_policy}}

### Result Interpretation

| Outcome | Criteria | Action |
|---------|----------|--------|
| Significant Win | {{win_criteria}} | {{win_action}} |
| Significant Loss | {{loss_criteria}} | {{loss_action}} |
| Inconclusive | {{inconc_criteria}} | {{inconc_action}} |
| Guardrail Violation | {{guard_criteria}} | {{guard_action}} |

---

## 7. Timeline

| Milestone | Date | Owner |
|-----------|------|-------|
| Test Plan Approval | {{approval_date}} | {{approval_owner}} |
| Implementation Complete | {{impl_date}} | {{impl_owner}} |
| QA Complete | {{qa_date}} | {{qa_owner}} |
| Test Launch | {{launch_date}} | {{launch_owner}} |
| Midpoint Review | {{mid_review_date}} | {{mid_owner}} |
| Test End | {{end_date}} | {{end_owner}} |
| Analysis Complete | {{analysis_date}} | {{analysis_owner}} |
| Decision Made | {{decision_date}} | {{decision_owner}} |

---

## 8. Risks and Mitigation

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| {{risk_1}} | {{like_1}} | {{impact_1}} | {{mit_1}} |
| {{risk_2}} | {{like_2}} | {{impact_2}} | {{mit_2}} |
| {{risk_3}} | {{like_3}} | {{impact_3}} | {{mit_3}} |

---

## 9. Pre-Launch Checklist

- [ ] Hypothesis documented and approved
- [ ] Sample size calculated
- [ ] Tracking implemented
- [ ] QA complete on both variants
- [ ] Monitoring dashboards ready
- [ ] Rollback plan documented
- [ ] Stakeholders informed

---

## Appendix

### Supporting Data

{{supporting_data}}

### References

{{references}}

---

*Generated by Dana - BMAD Data Analyst*
