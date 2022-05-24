import base64
import binascii
import uuid

import six
from django.core.files.base import ContentFile
from django.utils.translation import gettext_lazy as _
from rest_framework import serializers


class Base64(object):
    def to_internal_value(self, data):

        header = ''

        # Check if this is a base64 string
        if isinstance(data, six.string_types):
            # Check if the base64 string is in the "data:" format
            if 'data:' in data and ';base64,' in data:
                # Break out the header from the base64 content
                header, data = data.split(';base64,')

            # Try to decode the file. Return validation error if it fails.
            try:
                decoded_file = base64.b64decode(data)
            except (binascii.Error, ValueError,):
                raise serializers.ValidationError(_('Received Base64 string is not correct.'))

            # Generate file name
            # 12 characters are more than enough
            file_name = str(uuid.uuid4())[:12]

            # Get the file name extension:
            file_extension = self.get_file_extension(header)
            if not file_extension:
                raise serializers.ValidationError(_('Received Base64 string is not correct.'))

            complete_file_name = "%s.%s" % (file_name, file_extension,)
            data = ContentFile(decoded_file, name=complete_file_name)

        return super().to_internal_value(data)

    def get_file_extension(self, header):

        extension = header.split('/')[-1]
        return extension


class Base64FileField(Base64, serializers.FileField):
    pass


class Base64ImageField(Base64, serializers.ImageField):
    pass
