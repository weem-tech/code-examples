import hashlib
import json
import random
import string

from django.core import serializers


def generate_unique_key(value, length=40):
    """
    generate key from passed value
    :param value:
    :param length: key length
    :return:
    """

    salt = ''.join(random.SystemRandom().choice(string.ascii_uppercase + string.digits) for _ in range(26)).encode('utf-8')
    value = value.encode('utf-8')
    unique_key = hashlib.sha1(salt + value).hexdigest()

    return unique_key[:length]


def model_to_dict(instance):
    """
    Generate dict object from received model instance
    :param instance:
    :return: dict
    """

    serialized_instance = json.loads(serializers.serialize('json', [instance, ]))[0]
    instance_dict = serialized_instance['fields']

    # add instance pk to the fields dict
    instance_dict['id'] = serialized_instance['pk']

    return instance_dict
