"""
Academic risk-scoring engine.

Deliberately implemented as an explainable, weighted rule model
rather than a black-box classifier: for a first version consumed
by academic staff, being able to say *why* a student was flagged
matters more than squeezing out extra accuracy. The score
function is written so a trained model (e.g. scikit-learn
LogisticRegression over historical outcomes) could later replace
`compute_risk()` without changing the API contract — same input
dict in, same shape of result out.
"""
from dataclasses import dataclass


@dataclass
class RiskResult:
    risk_level: str          # "low" | "medium" | "high"
    risk_score: float        # 0.0 (safest) – 1.0 (highest risk)
    reasons: list
    recommended_action: str


def compute_risk(attendance_rate, classes_logged) -> RiskResult:
    """
    attendance_rate: float 0.0–1.0, or None if no classes logged yet.
    classes_logged: int, how many attendance records exist.
    """
    reasons = []

    # Not enough data yet to say anything meaningful.
    if attendance_rate is None or classes_logged < 3:
        return RiskResult(
            risk_level='insufficient_data',
            risk_score=0.0,
            reasons=['Fewer than 3 attendance records logged for this student.'],
            recommended_action='Continue routine attendance logging before assessment.',
        )

    # Base score: inverse of attendance rate.
    score = 1.0 - attendance_rate

    if attendance_rate < 0.60:
        reasons.append(f'Attendance rate is {attendance_rate:.0%}, well below the 75% norm.')
    elif attendance_rate < 0.75:
        reasons.append(f'Attendance rate is {attendance_rate:.0%}, below the 75% norm.')
        score += 0.05  # push borderline cases into "medium" rather than "low"
    else:
        reasons.append(f'Attendance rate is {attendance_rate:.0%}, at or above the 75% norm.')

    # Low sample size makes any score less certain — nudge toward
    # caution rather than a confident "low risk" label.
    if classes_logged < 10:
        reasons.append(f'Only {classes_logged} classes logged so far — score may shift as more data arrives.')
        score += 0.05

    score = max(0.0, min(1.0, score))

    if score >= 0.55:
        level = 'high'
        action = 'Flag to course coordinator for a check-in with the student this week.'
    elif score >= 0.30:
        level = 'medium'
        action = 'Monitor over the next 2 weeks; no intervention needed yet.'
    else:
        level = 'low'
        action = 'No action needed.'

    return RiskResult(risk_level=level, risk_score=round(score, 3), reasons=reasons, recommended_action=action)
