# -*- coding: utf-8 -*-
from project.settings.base import *

DEBUG = True

BASE_PATH = '/var/www/'
DASHBOARD_URL = ''
BACKEND_URL = ''

# ActionML UR
UR_URL = ''
UR_ENGINE = 'engine_1'

# Application definition
INSTALLED_APPS = INSTALLED_APPS + THIRD_PARTY_APPS + PROJECT_APPS

# Database
DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.postgresql_psycopg2',
        'NAME': 'project_api',
        'USER': 'project_user',
        'PASSWORD': '',
        'HOST': '127.0.0.1',
        'PORT': '5432'
    }
}
