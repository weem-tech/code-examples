from django.contrib.admin import AdminSite


class MyAdminSite(AdminSite):
    site_header = 'Api administration'


admin_site = MyAdminSite(name='my_admin')
