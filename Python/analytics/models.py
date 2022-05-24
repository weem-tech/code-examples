from django.contrib.postgres.fields import JSONField
from django.core.validators import MinValueValidator
from django.db import models

from apps.core.models import AbstractBaseModel


class TrackSession(AbstractBaseModel):
    customer_id = models.PositiveIntegerField(
        validators=[MinValueValidator(1)]
    )

    shop_id = models.PositiveIntegerField(
        validators=[MinValueValidator(1)]
    )

    session_id = models.CharField(
        verbose_name='Session Id',
        max_length=32,
        null=False,
        blank=False
    )

    class Meta:
        verbose_name_plural = 'Analytics Session'
        unique_together = ('customer_id', 'shop_id', 'session_id')


class TrackData(AbstractBaseModel):
    types = {
        'other': 0,
        'product': 1,
        'category': 2,
        'search': 3
    }

    session = models.ForeignKey(
        TrackSession,
        on_delete=models.CASCADE,
        default=None,
        null=True,
        blank=True
    )

    uuid = models.CharField(
        verbose_name='UUID',
        max_length=32,
        null=False,
        blank=False,
    )

    url = models.CharField(
        max_length=255,
        null=True,
        blank=True,
    )

    type = models.IntegerField(
        validators=[MinValueValidator(0)]
    )

    data = JSONField(null=True, blank=True)

    class Meta:
        verbose_name_plural = 'Analytics Data'
        indexes = [
            models.Index(fields=['created_at', ]),
        ]
