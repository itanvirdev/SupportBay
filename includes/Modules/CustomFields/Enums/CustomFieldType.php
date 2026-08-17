<?php

declare(strict_types=1);

namespace SupportBay\Modules\CustomFields\Enums;

enum CustomFieldType: string {
  case TEXT = 'text';
  case TEXTAREA = 'textarea';
  case NUMBER = 'number';
  case SELECT = 'select';
  case CHECKBOX = 'checkbox';
  case DATE = 'date';
  case EMAIL = 'email';
  case URL = 'url';
}
