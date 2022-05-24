from django.contrib import admin

from apps.analytics.models import TrackSession
from apps.core.admin import admin_site


class SessionModelAdmin(admin.ModelAdmin):
    list_display = [
        'customer_id',
        'shop_id',
        'session_id',
    ]
    search_fields = ['customer_id', 'shop_id', 'session_id']


admin_site.register(TrackSession, SessionModelAdmin)
