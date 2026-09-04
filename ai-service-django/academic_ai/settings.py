"""
Django settings — Academic AI microservice.

This service does exactly one job: expose AI-driven analytics
(risk scoring, performance insight) as a REST API that the PHP
backend calls server-to-server. It owns no user-facing auth of
its own; it trusts requests from the PHP layer, which is why in
production this would sit behind an internal network / API key
shared only with the PHP service (see SHARED_SERVICE_KEY below).
"""
from pathlib import Path
import os

BASE_DIR = Path(__file__).resolve().parent.parent

SECRET_KEY = os.environ.get('DJANGO_SECRET_KEY', 'demo-secret-change-me-in-production')
DEBUG = os.environ.get('DJANGO_DEBUG', 'True') == 'True'
ALLOWED_HOSTS = ['*']  # tighten to the PHP host's IP/domain in production

# Service-to-service auth: the PHP backend sends this in an
# `X-Service-Key` header. A lightweight alternative to full OAuth
# for an internal microservice-to-microservice call.
SHARED_SERVICE_KEY = os.environ.get('AI_SERVICE_SHARED_KEY', 'demo-shared-key-change-me')

INSTALLED_APPS = [
    'django.contrib.contenttypes',
    'django.contrib.staticfiles',
    'rest_framework',
    'corsheaders',
    'analytics',
]

MIDDLEWARE = [
    'corsheaders.middleware.CorsMiddleware',
    'django.middleware.common.CommonMiddleware',
]

CORS_ALLOW_ALL_ORIGINS = True  # demo only — scope this down in production

ROOT_URLCONF = 'academic_ai.urls'
WSGI_APPLICATION = 'academic_ai.wsgi.application'

DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.sqlite3',
        'NAME': BASE_DIR / 'ai_service.sqlite3',
    }
}

REST_FRAMEWORK = {
    'DEFAULT_RENDERER_CLASSES': ['rest_framework.renderers.JSONRenderer'],
    # This service has no Django user model (django.contrib.auth isn't
    # installed — see the module docstring above: auth is a shared-secret
    # header, not session/user auth). DRF's request.user machinery defaults
    # to importing AnonymousUser from django.contrib.auth.models, which
    # breaks without that app installed. Explicitly disabling both here
    # tells DRF not to touch that machinery at all.
    'DEFAULT_AUTHENTICATION_CLASSES': [],
    'UNAUTHENTICATED_USER': None,
}

LANGUAGE_CODE = 'en-us'
TIME_ZONE = 'Asia/Dhaka'
USE_I18N = True
USE_TZ = True

STATIC_URL = 'static/'
DEFAULT_AUTO_FIELD = 'django.db.models.BigAutoField'
