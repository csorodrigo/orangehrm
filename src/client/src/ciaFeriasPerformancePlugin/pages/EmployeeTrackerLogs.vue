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
    <div class="cia-ferias-card-container">
      <div class="cia-ferias-employee-tracker">
        <div class="cia-ferias-employee-tracker-image-section">
          <div class="cia-ferias-employee-tracker-image-wrapper">
            <img alt="profile picture" class="employee-image" :src="imgSrc" />
          </div>
        </div>
        <div class="cia-ferias-employee-tracker-header-section">
          <div class="cia-ferias-employee-tracker-header">
            <oxd-text tag="h5" class="cia-ferias-employee-tracker-header-title">
              {{ trackerName }}
            </oxd-text>
            <oxd-text
              tag="h6"
              class="cia-ferias-employee-tracker-header-subtitle"
            >
              {{ employeeName }}
            </oxd-text>
          </div>
          <div class="cia-ferias-employee-tracker-ratings">
            <div
              v-if="meta.positive > 0"
              class="cia-ferias-employee-tracker-ratings-info"
            >
              <oxd-icon
                class="cia-ferias-employee-tracker-ratings-icon --positive"
                type="svg"
                name="thumbsup"
              />
              <oxd-text
                class="cia-ferias-employee-tracker-ratings-text --positive"
              >
                {{ meta.positive }}
              </oxd-text>
            </div>
            <div
              v-if="meta.negative > 0"
              class="cia-ferias-employee-tracker-ratings-info"
            >
              <oxd-icon
                class="cia-ferias-employee-tracker-ratings-icon --negative"
                type="svg"
                name="thumbsdown"
              />
              <oxd-text
                class="cia-ferias-employee-tracker-ratings-text --negative"
              >
                {{ meta.negative }}
              </oxd-text>
            </div>
          </div>
        </div>
      </div>
    </div>
    <br />
    <div class="cia-ferias-paper-container">
      <div class="cia-ferias-header-container">
        <oxd-text tag="h5" class="cia-ferias-employee-tracker-list-header">
          {{ $t('performance.tracker_logs') }}
        </oxd-text>
        <oxd-button
          :label="$t('performance.add_log')"
          icon-name="plus"
          display-type="secondary"
          @click="onClickAdd"
        />
      </div>
      <div ref="scrollerRef" class="cia-ferias-container">
        <oxd-sheet
          v-for="(item, index) in items"
          :key="index"
          :gutters="false"
          type="gray-lighten-2"
          class="cia-ferias-scroll-card"
        >
          <employee-tracker-log-card
            :tracker-log="item"
            @edit="onClickEdit"
            @delete="onClickDelete"
          />
        </oxd-sheet>
        <div
          v-if="showNoRecordsFound"
          class="cia-ferias-employee-tracker-no-records"
        >
          <oxd-text>
            {{ $t('general.n_records_found', {count: 0}) }}
          </oxd-text>
        </div>
        <oxd-loading-spinner
          v-if="isLoading"
          class="cia-ferias-container-loader"
        />
      </div>
    </div>
    <add-tracker-log-modal
      v-if="showAddTrackerModal"
      :tracker-id="trackerId"
      @close="onAddTrackerModalClose"
    ></add-tracker-log-modal>
    <edit-tracker-log-modal
      v-if="showEditTrackerModal"
      :tracker-id="trackerId"
      :tracker-log-id="editTrackerLogId"
      @close="onEditTrackerModalClose"
    ></edit-tracker-log-modal>
    <delete-confirmation ref="deleteDialog"></delete-confirmation>
  </div>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';
import {reactive, toRefs, computed} from 'vue';
import {formatDate, parseDate} from '@cia-ferias/core/util/helper/datefns';
import useEmployeeNameTranslate from '@/core/util/composable/useEmployeeNameTranslate';
import useDateFormat from '@/core/util/composable/useDateFormat';
import useLocale from '@/core/util/composable/useLocale';
import useInfiniteScroll from '@/core/util/composable/useInfiniteScroll';
import AddTrackerLogModal from '@/ciaFeriasPerformancePlugin/components/AddTrackerLogModal';
import EditTrackerLogModal from '@/ciaFeriasPerformancePlugin/components/EditTrackerLogModal';
import DeleteConfirmationDialog from '@/core/components/dialogs/DeleteConfirmationDialog';
import EmployeeTrackerLogCard from '@/ciaFeriasPerformancePlugin/components/EmployeeTrackerLogCard';
import {OxdIcon, OxdSheet, OxdSpinner} from '@cia-ferias/oxd';

export default {
  name: 'ViewEmployeeTrackerLogs',
  components: {
    'oxd-icon': OxdIcon,
    'oxd-sheet': OxdSheet,
    'oxd-loading-spinner': OxdSpinner,
    'add-tracker-log-modal': AddTrackerLogModal,
    'edit-tracker-log-modal': EditTrackerLogModal,
    'delete-confirmation': DeleteConfirmationDialog,
    'employee-tracker-log-card': EmployeeTrackerLogCard,
  },
  props: {
    trackerId: {
      type: Number,
      required: true,
    },
    empNumber: {
      type: Number,
      required: true,
    },
  },
  setup(props) {
    const http = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/performance/trackers/${props.trackerId}/logs`,
    );
    const limit = 10;
    const state = reactive({
      total: 0,
      items: [],
      meta: {
        positive: 0,
        negative: 0,
      },
      infinite: false,
      isLoading: false,
      showNoRecordsFound: false,
    });

    const {$tEmpName} = useEmployeeNameTranslate();
    const {jsDateFormat} = useDateFormat();
    const {locale} = useLocale();

    const fetchData = () => {
      state.showNoRecordsFound = false;
      state.isLoading = true;
      http
        .getAll({
          limit: limit,
          offset: state.items.length === 0 ? 0 : limit,
        })
        .then((response) => {
          const {data, meta} = response.data;
          state.total = meta?.total || 0;
          if (Array.isArray(data)) {
            state.items = [
              ...state.items,
              ...data.map((item) => {
                return {
                  ...item,
                  reviewerPictureSrc: `${window.appGlobal.baseUrl}/pim/viewPhoto/empNumber/${item.reviewer.empNumber}`,
                  reviewerName: $tEmpName(item.reviewer),
                  addedDate: formatDate(
                    parseDate(item.addedDate),
                    jsDateFormat,
                    {locale},
                  ),
                  modifiedDate: formatDate(
                    parseDate(item.modifiedDate),
                    jsDateFormat,
                    {locale},
                  ),
                };
              }),
            ];
            state.meta = {...state.meta, ...meta};
          }
        })
        .finally(() => {
          state.showNoRecordsFound = state.total === 0;
          state.isLoading = false;
        });
    };

    const {scrollerRef} = useInfiniteScroll(() => {
      if (state.items.length >= state.total) return;
      fetchData();
    });

    const imgSrc = computed(() => {
      return `${window.appGlobal.baseUrl}/pim/viewPhoto/empNumber/${props.empNumber}`;
    });

    return {
      http,
      scrollerRef,
      fetchData,
      ...toRefs(state),
      imgSrc,
    };
  },
  data() {
    return {
      trackerName: '',
      employeeName: '',
      showAddTrackerModal: false,
      showEditTrackerModal: false,
      editTrackerLogId: null,
    };
  },
  beforeMount() {
    this.http
      .request({
        method: 'GET',
        url: `/api/v2/performance/employees/trackers/${this.trackerId}`,
      })
      .then((response) => {
        const {data} = response.data;
        this.trackerName = data.trackerName;
        this.employeeName = `${data.employee.firstName} ${
          data.employee.lastName
        } ${
          data.employee.terminationId
            ? ` ${this.$t('general.past_employee')}`
            : ''
        }`;
      })
      .then(() => {
        this.fetchData();
      });
  },
  methods: {
    onClickAdd() {
      this.showAddTrackerModal = true;
    },
    onAddTrackerModalClose() {
      this.showAddTrackerModal = false;
      this.resetItems();
    },
    onEditTrackerModalClose() {
      this.showEditTrackerModal = false;
      this.resetItems();
    },
    onClickEdit(id) {
      this.editTrackerLogId = id;
      this.showEditTrackerModal = true;
    },
    onClickDelete(id) {
      this.$refs.deleteDialog.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          this.deleteItems([id]);
        }
      });
    },
    deleteItems(items) {
      if (items instanceof Array) {
        this.items = [];
        this.isLoading = true;
        this.http
          .deleteAll({
            ids: items,
          })
          .then(() => {
            return this.$toast.deleteSuccess();
          })
          .finally(() => {
            this.fetchData();
          });
      }
    },
    resetItems() {
      this.items = [];
      this.fetchData();
    },
  },
};
</script>

<style src="./employee-tracker-log.scss" lang="scss" scoped></style>
