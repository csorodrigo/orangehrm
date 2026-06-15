<!--
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */
 -->

<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text class="orangehrm-main-title">
        {{ $t('leave.leave_period') }}
      </oxd-text>

      <oxd-divider />

      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="4" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="leavePeriod.startMonth"
                type="select"
                :options="months"
                :rules="rules.startMonth"
                :label="$t('leave.start_month')"
                required
              />
            </oxd-grid-item>

            <oxd-grid-item>
              <oxd-input-field
                v-model="leavePeriod.startDay"
                type="select"
                :options="dates"
                :rules="rules.startDay"
                :label="$t('general.start_date')"
                required
              />
            </oxd-grid-item>

            <oxd-grid-item>
              <oxd-input-field
                v-model="leavePeriod.endMonth"
                type="select"
                :options="months"
                :rules="rules.endMonth"
                label="Mês de término"
                required
              />
            </oxd-grid-item>

            <oxd-grid-item>
              <oxd-input-field
                v-model="leavePeriod.endDay"
                type="select"
                :options="endDates"
                :rules="rules.endDay"
                :label="$t('general.end_date')"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-form-row v-if="leavePeriod.currentPeriod">
          <oxd-grid :cols="4" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-group :label="$t('leave.current_leave_period')">
                <oxd-text type="subtitle-2" class="orangehrm-leave-period">
                  {{ leavePeriod.currentPeriod }}
                </oxd-text>
              </oxd-input-group>
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-divider />

        <oxd-form-actions>
          <required-text />
          <oxd-button
            display-type="ghost"
            :label="$t('general.reset')"
            @click="onClickReset"
          />
          <submit-button />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {reloadPage} from '@ohrm/core/util/helper/navigation';
import {required} from '@/core/util/validation/rules';
import {formatDate, parseDate} from '@/core/util/helper/datefns';
import useDateFormat from '@/core/util/composable/useDateFormat';
import useLocale from '@/core/util/composable/useLocale';

const leavePeriodModel = {
  startMonth: null,
  startDay: null,
  endMonth: null,
  endDay: null,
  currentPeriod: null,
};

export default {
  props: {
    monthDates: {
      type: Object,
      required: true,
    },
  },

  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/leave/leave-period',
    );
    const {jsDateFormat} = useDateFormat();
    const {locale} = useLocale();
    return {
      http,
      jsDateFormat,
      locale,
    };
  },

  data() {
    return {
      isLoading: false,
      leavePeriod: {...leavePeriodModel},
      leavePeriodDefined: true,
      rules: {
        startMonth: [required],
        startDay: [required],
        endMonth: [required],
        endDay: [required],
      },
    };
  },

  computed: {
    months() {
      return Array(12)
        .fill('')
        .map((...[, index]) => {
          return {
            id: index + 1,
            label: this.locale.localize.month(index, {
              width: 'wide',
            }),
          };
        });
    },
    dates() {
      return (this.monthDates[this.leavePeriod.startMonth?.id] ?? []).map(
        (day) => {
          return {
            id: day,
            label: String(day).padStart(2, '0'),
          };
        },
      );
    },
    endDates() {
      return (this.monthDates[this.leavePeriod.endMonth?.id] ?? []).map(
        (day) => {
          return {
            id: day,
            label: String(day).padStart(2, '0'),
          };
        },
      );
    },
  },

  watch: {
    'leavePeriod.startMonth': function () {
      this.leavePeriod.startDay = this.dates.length > 0 ? this.dates[0] : null;
    },
    'leavePeriod.endMonth': function () {
      this.leavePeriod.endDay =
        this.endDates.length > 0 ? this.endDates[0] : null;
    },
  },

  beforeMount() {
    this.isLoading = true;
    this.http
      .request({
        method: 'GET',
      })
      .then((response) => {
        const {data, meta} = response.data;
        this.updateLeavePeriodModel(data);
        this.defineLeavePeriod(meta);
        this.resetLeavePeriod();
      })
      .finally(() => {
        this.isLoading = false;
      });
  },

  methods: {
    onSave() {
      this.isLoading = true;
      this.http
        .request({
          method: 'PUT',
          data: {
            startMonth: this.leavePeriod.startMonth?.id,
            startDay: this.leavePeriod.startDay?.id,
            endMonth: this.leavePeriod.endMonth?.id,
            endDay: this.leavePeriod.endDay?.id,
          },
        })
        .then((response) => {
          const {data, meta} = response.data;
          this.updateLeavePeriodModel(data);
          this.defineLeavePeriod(meta);
          this.resetLeavePeriod();
          return this.$toast.saveSuccess();
        })
        .then(() => {
          this.isLoading = false;
          if (!this.leavePeriodDefined) {
            reloadPage();
          }
        });
    },

    onClickReset() {
      this.resetLeavePeriod();
    },

    resetLeavePeriod() {
      this.leavePeriod.startMonth = leavePeriodModel.startMonth;
      this.leavePeriod.endMonth = leavePeriodModel.endMonth;
      this.$nextTick(() => {
        this.leavePeriod.startDay = leavePeriodModel.startDay;
        this.leavePeriod.endDay = leavePeriodModel.endDay;
      });
    },

    updateLeavePeriodModel(data) {
      leavePeriodModel.startMonth = this.months.find((m) => {
        return m.id === data.startMonth;
      });
      leavePeriodModel.startDay = this.getDateOption(
        data.startMonth,
        data.startDay,
      );
      leavePeriodModel.endMonth = this.months.find((m) => {
        return m.id === data.endMonth;
      });
      leavePeriodModel.endDay = this.getDateOption(data.endMonth, data.endDay);
    },

    getDateOption(month, day) {
      return (this.monthDates[month] ?? [])
        .map((date) => {
          return {
            id: date,
            label: String(date).padStart(2, '0'),
          };
        })
        .find((date) => {
          return date.id === day;
        });
    },

    defineLeavePeriod(meta) {
      if (meta.leavePeriodDefined === true) {
        this.leavePeriodDefined = meta.leavePeriodDefined;
        const startDate = formatDate(
          parseDate(meta.currentLeavePeriod.startDate),
          this.jsDateFormat,
          {locale: this.locale},
        );
        const endDate = formatDate(
          parseDate(meta.currentLeavePeriod.endDate),
          this.jsDateFormat,
          {locale: this.locale},
        );
        this.leavePeriod.currentPeriod = `${startDate} ${this.$t(
          'general.to',
        ).toLowerCase()} ${endDate}`;
      } else {
        this.leavePeriodDefined = false;
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-leave-duration {
  padding: $oxd-input-control-vertical-padding 0rem;
}
</style>
