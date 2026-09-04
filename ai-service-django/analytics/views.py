from django.conf import settings
from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status

from .serializers import RiskScoreRequestSerializer
from .risk_engine import compute_risk


class ServiceKeyPermission:
    """
    Lightweight shared-secret check between the PHP backend and
    this service. Not a substitute for a full OAuth setup, but
    appropriate for a single trusted internal caller — and it's
    the kind of pragmatic call an Academic AI Program Lead would
    need to make and justify.
    """
    @staticmethod
    def is_authorized(request) -> bool:
        return request.headers.get('X-Service-Key') == settings.SHARED_SERVICE_KEY


class RiskScoreView(APIView):
    """
    POST /api/risk-score/
    Body: { "student_id": 5, "attendance_rate": 0.62, "classes_logged": 14 }
    """

    def post(self, request):
        if not ServiceKeyPermission.is_authorized(request):
            return Response({'error': 'Invalid service key'}, status=status.HTTP_401_UNAUTHORIZED)

        serializer = RiskScoreRequestSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        data = serializer.validated_data

        result = compute_risk(data.get('attendance_rate'), data.get('classes_logged', 0))

        return Response({
            'student_id': data['student_id'],
            'risk_level': result.risk_level,
            'risk_score': result.risk_score,
            'reasons': result.reasons,
            'recommended_action': result.recommended_action,
        })


class HealthCheckView(APIView):
    """GET /api/health/ — simple liveness check for the PHP layer to ping."""

    def get(self, request):
        return Response({'status': 'ok', 'service': 'academic-ai-analytics'})
