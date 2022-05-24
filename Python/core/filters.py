import datetime


class BaseFilter(object):
    """
    BaseFilter which includes base filter methods.
    This Filter should be extended.
    """

    def filter_created(self, queryset, name, value):
        return queryset.filter(created__startswith=value)

    def filter_user_created(self, queryset, name, value):
        return queryset.filter(user__created__startswith=value)

    def filter_created_to(self, queryset, name, value):
        return queryset.filter(created__lte=value + datetime.timedelta(days=1))
