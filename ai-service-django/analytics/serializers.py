from rest_framework import serializers


class RiskScoreRequestSerializer(serializers.Serializer):
    student_id = serializers.IntegerField()
    attendance_rate = serializers.FloatField(required=False, allow_null=True)
    classes_logged = serializers.IntegerField(required=False, default=0)
