# No persistent models needed yet — risk scoring is computed
# on demand from data the PHP service already owns. If the
# dashboard later wants historical risk trends, this is where
# a RiskAssessment(student_id, score, level, created_at) model
# would be added, with a migration to match.
