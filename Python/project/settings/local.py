# -*- coding: utf-8 -*-
from project.settings.base import *

DEBUG = True

BASE_PATH = "/var/www/"
BACKEND_URL = 'api.loc'

UR_URL = 'http://localhost:9090/'
UR_ENGINE = 'engine'

INSTALLED_APPS = INSTALLED_APPS + THIRD_PARTY_APPS + PROJECT_APPS

# Database
DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.postgresql_psycopg2',
        'NAME': '',
        'USER': '',
        'PASSWORD': '',
        'HOST': '127.0.0.1',
        'PORT': '5432'
    }
}
