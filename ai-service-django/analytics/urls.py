from django.urls import path
from .views import RiskScoreView, HealthCheckView

urlpatterns = [
    path('risk-score/', RiskScoreView.as_view(), name='risk-score'),
    path('health/', HealthCheckView.as_view(), name='health'),
]
