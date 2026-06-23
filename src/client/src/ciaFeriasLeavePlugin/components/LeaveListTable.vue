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
  <div class="cia-ferias-background-container">
    <slot
      :filters="filters"
      :rules="rules"
      :filter-items="filterItems"
      :on-reset="onReset"
    ></slot>
    <br />
    <div class="cia-ferias-paper-container">
      <leave-list-table-header
        :selected="checkedItems.length"
        :total="total"
        :loading="isLoading"
        :bulk-actions="leaveBulkActions"
        @on-action-click="onLeaveActionBulk"
      >
      </leave-list-table-header>
      <div v-if="quickLeaveRequest" class="ca-leave-confirmation">
        <div class="ca-leave-confirmation__details">
          <oxd-text tag="h6" class="ca-leave-confirmation__title">
            Confirmar solicitação de férias
          </oxd-text>
          <div class="ca-leave-confirmation__grid">
            <span>Colaborador</span>
            <strong>{{ quickLeaveRequest.employeeName }}</strong>
            <span>Período</span>
            <strong>{{ quickLeaveRequest.date }}</strong>
            <span>Tipo</span>
            <strong>{{ quickLeaveRequest.leaveType }}</strong>
            <span>Saldo</span>
            <strong>{{ quickLeaveRequest.leaveBalance }}</strong>
            <span>Dias</span>
            <strong>{{ quickLeaveRequest.days }}</strong>
            <span>Status</span>
            <strong>{{ quickLeaveRequest.status }}</strong>
          </div>
        </div>
        <div class="ca-leave-confirmation__actions">
          <oxd-button
            v-if="quickActionAvailable('REJECT')"
            label="Rejeitar"
            display-type="label-danger"
            @click="onLeaveAction(quickLeaveRequest.id, 'REJECT')"
          />
          <oxd-button
            v-if="quickActionAvailable('APPROVE')"
            label="Aprovar"
            display-type="label-success"
            @click="onLeaveAction(quickLeaveRequest.id, 'APPROVE')"
          />
        </div>
      </div>
      <div class="cia-ferias-container">
        <oxd-card-table
          v-model:selected="checkedItems"
          :headers="headers"
          :items="items?.data"
          :selectable="true"
          :clickable="false"
          :loading="isLoading"
          row-decorator="oxd-table-decorator-card"
        />
      </div>
      <div class="cia-ferias-bottom-container">
        <oxd-pagination
          v-if="showPaginator"
          v-model:current="currentPage"
          :length="pages"
        />
      </div>
    </div>
  </div>
  <leave-comment-modal
    v-if="showCommentModal"
    :id="commentModalState"
    @close="onCommentModalClose"
  >
  </leave-comment-modal>
  <leave-bulk-action-modal ref="bulkActionModal" :data="bulkActionModalState">
  </leave-bulk-action-modal>
</template>

<script>
import {
  required,
  validSelection,
  validDateFormat,
  endDateShouldBeAfterStartDate,
  shouldNotExceedCharLength,
} from '@/core/util/validation/rules';
import {computed, ref} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import {navigate} from '@cia-ferias/core/util/helper/navigation';
import {truncate} from '@cia-ferias/core/util/helper/truncate';
import usePaginate from '@cia-ferias/core/util/composable/usePaginate';
import useLeaveActions from '@/ciaFeriasLeavePlugin/util/composable/useLeaveActions';
import LeaveCommentsModal from '@/ciaFeriasLeavePlugin/components/LeaveCommentsModal';
import LeaveBulkActionModal from '@/ciaFeriasLeavePlugin/components/LeaveBulkActionModal';
import LeaveListTableHeader from '@/ciaFeriasLeavePlugin/components/LeaveListTableHeader';
import usei18n from '@/core/util/composable/usei18n';
import useDateFormat from '@/core/util/composable/useDateFormat';
import {formatDate, parseDate} from '@/core/util/helper/datefns';
import useLocale from '@/core/util/composable/useLocale';

const defaultFilters = {
  employee: null,
  fromDate: null,
  toDate: null,
  statuses: [],
  subunit: null,
  includePastEmps: false,
  leaveType: null,
};

export default {
  name: 'LeaveListTable',

  components: {
    'leave-list-table-header': LeaveListTableHeader,
    'leave-comment-modal': LeaveCommentsModal,
    'leave-bulk-action-modal': LeaveBulkActionModal,
  },

  props: {
    myLeaveList: {
      type: Boolean,
      default: false,
    },
    leaveStatuses: {
      type: Array,
      default: () => [],
    },
    employee: {
      type: Object,
      required: false,
      default: () => null,
    },
    leaveType: {
      type: Object,
      required: false,
      default: () => null,
    },
    fromDate: {
      type: String,
      required: false,
      default: null,
    },
    toDate: {
      type: String,
      required: false,
      default: null,
    },
    leaveStatus: {
      type: Object,
      required: false,
      default: () => null,
    },
  },

  setup(props) {
    const filters = ref({
      ...defaultFilters,
      ...(props.leaveType && {leaveType: props.leaveType}),
      ...(props.fromDate && {fromDate: props.fromDate}),
      ...(props.toDate && {toDate: props.toDate}),
      ...(props.leaveStatus && {statuses: [props.leaveStatus]}),
      ...(props.employee && {
        employee: {
          id: props.employee.empNumber,
          label: `${props.employee.firstName} ${props.employee.middleName} ${props.employee.lastName}`,
          isPastEmployee: props.employee.terminationId,
        },
      }),
    });
    const checkedItems = ref([]);
    const {$t} = usei18n();
    const {locale} = useLocale();
    const {jsDateFormat, userDateFormat} = useDateFormat();

    const rules = {
      fromDate: [required, validDateFormat(userDateFormat)],
      toDate: [
        required,
        validDateFormat(userDateFormat),
        endDateShouldBeAfterStartDate(
          () => filters.value.fromDate,
          $t('general.to_date_should_be_after_from_date'),
          {allowSameDate: true},
        ),
      ],
      statuses: [required],
      employee: [shouldNotExceedCharLength(100), validSelection],
    };

    const serializedFilters = computed(() => {
      const statuses = Array.isArray(filters.value.statuses)
        ? filters.value.statuses
        : [];

      return {
        empNumber: filters.value.employee?.id,
        fromDate: filters.value.fromDate,
        toDate: filters.value.toDate,
        subunitId: filters.value.subunit?.id,
        includeEmployees: filters.value.includePastEmps
          ? 'currentAndPast'
          : 'onlyCurrent',
        statuses: statuses.map((item) => item.id),
        leaveTypeId: filters.value.leaveType?.id,
      };
    });

    const http = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/leave/${
        props.myLeaveList ? 'leave-requests' : 'employees/leave-requests'
      }`,
    );

    const leavelistNormalizer = (data) => {
      return data.map((item) => {
        let leaveDatePeriod,
          leaveStatuses,
          leaveBalances = '';
        const duration = item.dates.durationType?.type;

        if (item.dates.fromDate) {
          leaveDatePeriod = formatDate(
            parseDate(item.dates.fromDate),
            jsDateFormat,
            {locale},
          );
        }
        if (item.dates.toDate) {
          leaveDatePeriod += ` até ${formatDate(
            parseDate(item.dates.toDate),
            jsDateFormat,
            {locale},
          )}`;
        }
        if (item.dates.startTime && item.dates.endTime) {
          leaveDatePeriod += ` (${item.dates.startTime} - ${item.dates.endTime})`;
        }
        if (
          duration === 'half_day_morning' ||
          duration === 'half_day_afternoon'
        ) {
          leaveDatePeriod += ` ${$t('leave.half_day')}`;
        }
        if (Array.isArray(item.leaveBreakdown)) {
          leaveStatuses = item.leaveBreakdown
            .map(
              (status) =>
                `${status.name} (${parseFloat(status.lengthDays).toFixed(2)})`,
            )
            .join(', ');
        }
        const hasPendingApproval =
          Array.isArray(item.leaveBreakdown) &&
          item.leaveBreakdown.length === 1 &&
          ['pending approval', 'pendente de aprovação'].includes(
            item.leaveBreakdown[0]?.name?.toLowerCase(),
          );
        const allowedActions =
          Array.isArray(item.allowedActions) && item.allowedActions.length > 0
            ? item.allowedActions
            : !props.myLeaveList && hasPendingApproval
            ? [{action: 'REJECT'}, {action: 'APPROVE'}]
            : item.allowedActions ?? [];
        if (Array.isArray(item.leaveBalances)) {
          if (item.leaveBalances.length > 1) {
            leaveBalances = item.leaveBalances
              .map(({period, balance}) => {
                const _balance = parseFloat(balance.balance).toFixed(2);
                const startDate = formatDate(
                  parseDate(period.startDate),
                  jsDateFormat,
                  {locale},
                );
                const endDate = formatDate(
                  parseDate(period.endDate),
                  jsDateFormat,
                  {locale},
                );
                return `${_balance} (${startDate} - ${endDate})`;
              })
              .join(', ');
          } else {
            const balance = item.leaveBalances[0]?.balance.balance;
            leaveBalances = balance ? parseFloat(balance).toFixed(2) : '0.00';
          }
        }

        const empName = `${item.employee?.firstName} ${item.employee?.middleName} ${item.employee?.lastName}`;
        const leaveTypeName = item.leaveType?.name;

        if (item.employee?.terminationId) {
          empName + ` (${$t('general.past_employee')})`;
        }
        if (item.leaveType?.deleted) {
          leaveTypeName + ` (${$t('general.deleted')})`;
        }

        return {
          id: item.id,
          empNumber: item.employee?.empNumber,
          date: leaveDatePeriod,
          employeeName: empName,
          leaveType: leaveTypeName,
          leaveBalance: leaveBalances,
          days: parseFloat(item.noOfDays).toFixed(2),
          status: leaveStatuses,
          comment: truncate(item.lastComment?.comment),
          actions: allowedActions,
        };
      });
    };

    const {
      leaveActions,
      processLeaveRequestAction,
      processLeaveRequestBulkAction,
    } = useLeaveActions(http);

    const {
      showPaginator,
      currentPage,
      total,
      pages,
      pageSize,
      response,
      isLoading,
      execQuery,
    } = usePaginate(http, {
      query: serializedFilters,
      normalizer: leavelistNormalizer,
    });

    const leaveBulkActions = computed(() => {
      if (checkedItems.value.length > 0 && response.value.data) {
        const allActions = checkedItems.value.map((item) => {
          return response.value.data[item].actions;
        });
        return {
          APPROVE: allActions.reduce(
            (approvable, actions) =>
              approvable && actions.find((i) => i.action === 'APPROVE'),
            true,
          ),
          REJECT: allActions.reduce(
            (rejectable, actions) =>
              rejectable && actions.find((i) => i.action === 'REJECT'),
            true,
          ),
          CANCEL: allActions.reduce(
            (cancelable, actions) =>
              cancelable && actions.find((i) => i.action === 'CANCEL'),
            true,
          ),
        };
      }
      return null;
    });

    return {
      http,
      showPaginator,
      currentPage,
      isLoading,
      total,
      pages,
      pageSize,
      execQuery,
      items: response,
      rules,
      filters,
      checkedItems,
      leaveActions,
      leaveBulkActions,
      processLeaveRequestAction,
      processLeaveRequestBulkAction,
    };
  },

  data() {
    return {
      headers: [
        {name: 'date', title: this.$t('general.date'), style: {flex: 1}},
        {
          name: 'employeeName',
          title: this.$t('general.employee_name'),
          style: {flex: 1},
        },
        {
          name: 'leaveType',
          title: this.$t('leave.leave_type'),
          style: {flex: 1},
        },
        {
          name: 'leaveBalance',
          title: this.$t('leave.leave_balance_days'),
          style: {flex: 1},
        },
        {
          name: 'days',
          title: this.$t('leave.number_of_days'),
          style: {flex: 1},
        },
        {name: 'status', title: this.$t('general.status'), style: {flex: 1}},
        {
          name: 'comment',
          title: this.$t('general.comments'),
          style: {flex: '5%'},
        },
        {
          name: 'action',
          slot: 'footer',
          title: this.$t('general.actions'),
          cellType: 'oxd-table-cell-actions',
          cellRenderer: this.cellRenderer,
          style: {
            flex: this.myLeaveList ? '10%' : '20%',
          },
        },
      ],
      showCommentModal: false,
      commentModalState: null,
      bulkActionModalState: null,
    };
  },

  computed: {
    quickLeaveRequest() {
      const data = this.items?.data ?? [];
      if (this.filters.employee && data.length === 1) {
        return data[0];
      }
      return null;
    },
  },

  beforeMount() {
    this.isLoading = true;
    if (this.filters.statuses.length === 0) {
      this.filters.statuses = this.myLeaveList
        ? this.leaveStatuses
        : this.leaveStatuses.filter((status) => status.id === 1);
    }
    this.http
      .request({method: 'GET', url: '/api/v2/leave/leave-periods'})
      .then((response) => {
        const {data, meta} = response.data;
        if (meta.leavePeriodDefined) {
          this.filters.fromDate =
            this.filters.fromDate ?? meta?.currentLeavePeriod.startDate;
          this.filters.toDate =
            this.filters.toDate ?? meta?.currentLeavePeriod.endDate;
        } else {
          this.filters.fromDate = this.filters.fromDate ?? data[0]?.startDate;
          this.filters.toDate = this.filters.toDate ?? data[0]?.endDate;
        }
      })
      .finally(() => {
        this.isLoading = false;
        Object.assign(defaultFilters, this.filters);
      });
  },

  methods: {
    cellRenderer(...[, , , row]) {
      const cellConfig = {};
      const {approve, reject, cancel, more} = this.leaveActions;
      const dropdownActions = [
        {label: this.$t('general.add_comment'), context: 'add_comment'},
        {label: this.$t('leave.view_leave_details'), context: 'leave_details'},
        {label: this.$t('leave.view_pim_info'), context: 'pim_details'},
      ];

      row.actions.map((item) => {
        if (item.action === 'APPROVE') {
          approve.props.label = this.$t('general.approve');
          approve.props.onClick = () => this.onLeaveAction(row.id, 'APPROVE');
          cellConfig.approve = approve;
        }
        if (item.action === 'REJECT') {
          reject.props.label = this.$t('general.reject');
          reject.props.onClick = () => this.onLeaveAction(row.id, 'REJECT');
          cellConfig.reject = reject;
        }
        if (item.action === 'CANCEL') {
          if (this.myLeaveList) {
            cancel.props.label = this.$t('general.cancel');
            cancel.props.onClick = () => this.onLeaveAction(row.id, 'CANCEL');
            cellConfig.reject = cancel;
          } else {
            dropdownActions.push({
              label: this.$t('leave.cancel_leave'),
              context: 'cancel_leave',
            });
          }
        }
      });

      more.props.options = dropdownActions;
      more.props.onClick = ($event) => this.onLeaveDropdownAction($event, row);
      cellConfig.more = more;

      return {
        props: {
          header: {
            cellConfig,
          },
        },
      };
    },
    onLeaveDropdownAction(event, item) {
      switch (event.context) {
        case 'add_comment':
          this.commentModalState = item.id;
          this.showCommentModal = true;
          break;
        case 'cancel_leave':
          this.onLeaveAction(item.id, 'CANCEL');
          break;
        case 'pim_details':
          navigate('/pim/viewPersonalDetails/empNumber/{id}', {
            id: item.empNumber,
          });
          break;
        default:
          navigate(
            '/leave/viewLeaveRequest/{id}',
            {id: item.id},
            this.myLeaveList && {mode: 'my-leave'},
          );
      }
    },
    onLeaveAction(id, actionType) {
      this.isLoading = true;
      this.processLeaveRequestAction(id, actionType)
        .then(() => {
          this.$toast.updateSuccess();
        })
        .finally(this.resetDataTable);
    },
    quickActionAvailable(actionType) {
      return (
        this.quickLeaveRequest?.actions?.some(
          (item) => item.action === actionType,
        ) ?? false
      );
    },
    async onLeaveActionBulk(actionType) {
      this.isLoading = true;
      this.bulkActionModalState = {
        count: this.checkedItems.length,
        action: actionType,
      };

      const ids = this.checkedItems.map((index) => {
        return this.items.data[index].id;
      });
      const confirmation = await this.$refs.bulkActionModal.showDialog();

      if (confirmation !== 'ok') {
        this.isLoading = false;
        return;
      }

      this.processLeaveRequestBulkAction(ids, actionType)
        .then((response) => {
          const {data} = response.data;
          if (Array.isArray(data))
            this.$toast.success({
              title: this.$t('general.success'),
              message: this.$t('leave.leave_requests_action', {
                action: actionType,
                count: data.length,
              }),
            });
        })
        .finally(() => {
          this.bulkActionModalState = null;
          this.resetDataTable();
        });
    },
    onCommentModalClose() {
      this.commentModalState = null;
      this.showCommentModal = false;
      this.resetDataTable();
    },
    async resetDataTable() {
      this.checkedItems = [];
      await this.execQuery();
    },
    async filterItems() {
      await this.execQuery();
    },
    onReset() {
      this.filters = {...defaultFilters};
      this.resetDataTable();
    },
  },
};
</script>

<style lang="scss" scoped>
::v-deep(.card-footer-slot) {
  .oxd-table-cell-actions {
    justify-content: flex-end;
  }
  .oxd-table-cell-actions > * {
    margin: 0 !important;
  }
}

.ca-leave-confirmation {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin: 0 1.2rem 1rem;
  padding: 1rem;
  border: 1px solid $oxd-interface-gray-lighten-2-color;
  border-radius: 0.5rem;
  background: $oxd-white-color;

  &__details {
    flex: 1;
  }

  &__title {
    margin: 0 0 0.75rem;
    color: $oxd-primary-one-color;
    font-weight: 700;
  }

  &__grid {
    display: grid;
    grid-template-columns: max-content minmax(0, 1fr);
    gap: 0.35rem 0.75rem;
    color: $oxd-interface-gray-color;
    font-size: 0.85rem;

    strong {
      color: $oxd-interface-gray-darken-1-color;
      font-weight: 700;
      word-break: break-word;
    }
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    align-content: flex-start;
    justify-content: flex-end;
    gap: 0.5rem;
  }
}

@include oxd-respond-to('xs') {
  .ca-leave-confirmation {
    flex-direction: column;

    &__actions {
      justify-content: flex-start;
    }
  }
}
</style>
