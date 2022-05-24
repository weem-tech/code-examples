from rest_framework import serializers

from apps.analytics.models import TrackSession


class SessionSerializer(serializers.ModelSerializer):
    id = serializers.ReadOnlyField()

    customer_id = serializers.IntegerField()

    shop_id = serializers.IntegerField()

    session_id = serializers.CharField(
        max_length=32,
        required=True
    )

    class Meta:
        model = TrackSession
        fields = '__all__'


class TrackDataSerializer(serializers.Serializer):
    customer_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    shop_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    session_id = serializers.CharField(
        max_length=32,
        required=True
    )

    uuid = serializers.CharField(
        max_length=32,
        required=True
    )

    url = serializers.URLField(
        required=False,
        allow_null=True,
        allow_blank=True,
    )

    type = serializers.IntegerField(
        required=True,
        min_value=0
    )

    data = serializers.JSONField()


class MergeCustomersSerializer(serializers.Serializer):
    master_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    slave_id = serializers.IntegerField(
        required=True,
        min_value=1
    )


class LastActionByTypeSerializer(serializers.Serializer):
    customer_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    shop_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    type = serializers.IntegerField(
        required=True,
        min_value=0
    )

    alt_type = serializers.IntegerField(
        required=False,
        min_value=0
    )

    session_id = serializers.CharField(
        max_length=32,
        required=False
    )


class LastSessionMostPopularSerializer(serializers.Serializer):
    customer_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    shop_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    session_id = serializers.CharField(
        max_length=32,
        required=False
    )

    exclude_id = serializers.CharField(
        required=False,
        allow_null=True
    )


class AutocompleteRegisteredUsersSerializer(serializers.Serializer):
    types = [
        ('hour', 'Hour'),
        ('day', 'Day'),
        ('month', 'Month'),
        ('year', 'Year')
    ]

    shop_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    start_date = serializers.DateField(
        required=False,
        allow_null=True
    )

    end_date = serializers.DateField(
        required=False,
        allow_null=True
    )

    group_type = serializers.ChoiceField(
        choices=types,
        required=False,
        allow_null=True
    )


class BasketPopupSerializer(serializers.Serializer):
    types = [
        ('hour', 'Hour'),
        ('day', 'Day'),
        ('month', 'Month'),
        ('year', 'Year')
    ]

    shop_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    start_date = serializers.DateField(
        required=False,
        allow_null=True
    )

    end_date = serializers.DateField(
        required=False,
        allow_null=True
    )

    group_type = serializers.ChoiceField(
        choices=types,
        required=False,
        allow_null=True
    )


class SessionProductsAnalyzeSerializer(serializers.Serializer):
    customer_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    shop_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    session_id = serializers.CharField(
        max_length=32,
        required=True
    )


class AnalyticDataByTypeSerializer(serializers.Serializer):
    shop_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    type = serializers.IntegerField(
        required=True,
        min_value=0
    )


class VouchersSerializer(serializers.Serializer):
    types = [
        ('hour', 'Hour'),
        ('day', 'Day'),
        ('month', 'Month'),
        ('year', 'Year')
    ]

    shop_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    start_date = serializers.DateField(
        required=False,
        allow_null=True
    )

    end_date = serializers.DateField(
        required=False,
        allow_null=True
    )

    group_type = serializers.ChoiceField(
        choices=types,
        required=False,
        allow_null=True
    )


class UserVouchersSerializer(serializers.Serializer):
    count = serializers.IntegerField(
        required=False,
        allow_null=True
    )
    date = serializers.DateTimeField(
        format="%Y-%m-%d",
        required=False,
        allow_null=True
    )


class UserVouchersTimeSerializer(serializers.Serializer):
    count = serializers.IntegerField(
        required=False,
        allow_null=True
    )
    date = serializers.DateTimeField(
        format="%H:%M:%S",
        required=False,
        allow_null=True
    )


class RecommendationSerializer(serializers.Serializer):
    types = [
        ('hour', 'Hour'),
        ('day', 'Day'),
        ('month', 'Month'),
        ('year', 'Year')
    ]

    shop_id = serializers.IntegerField(
        required=True,
        min_value=1
    )

    start_date = serializers.DateField(
        required=False,
        allow_null=True
    )

    end_date = serializers.DateField(
        required=False,
        allow_null=True
    )

    group_type = serializers.ChoiceField(
        choices=types,
        required=False,
        allow_null=True
    )
