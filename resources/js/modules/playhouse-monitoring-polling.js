import '../bootstrap.js'
import '../config/global.js'
import { showConsole } from '../config/debug.js'
import { dateToString, timeAgo } from '../utilities/dateString.js'
import { renderPagination } from "../utilities/pagination.js";
import { emptyStateTable, tableSkeleton } from '../components/tablePlaceholders.js'

let searchIn
let searchState = ''
let searchBtn
let meta = null
let dataRowsBody
let pageState = 1
let refreshInterval = null

const api = window.axios

const renderTableRows = (items) => {
    dataRowsBody.innerHTML = ''

    if(items.length === 0) {
        dataRowsBody.innerHTML = emptyStateTable()
    }

    dataRowsBody.innerHTML = items.map(item => tableRow(parseItem(item))).join('')
}

const parseItem = (item) => {
    const childName = item.child
        ? `${item.child.firstname} ${item.child.lastname}`
        : "N/A";

    const parentName = item.order.parent_pl
        ? item.order.parent_pl.d_name
        : (item.guardian ?? "N/A");

    const durationHours = !item.durationhours
        ? "N/A"
        : item.durationhours == 5
          ? "Unlimited"
          : `${item.durationhours}hr`;

    const checkedIn = item.ckin
        ? timeAgo(item.ckin)
        : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-200 text-gray-800">Not started</span>';

    let checkedOut;
    if (!item.ckin && !item.ckout) {
        checkedOut = `<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-orange-200 text-gray-800">Not started</span>`;
    } else if (item.ckin && !item.ckout) {
        checkedOut = `<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-200 text-gray-800">Active</span>`;
    } else {
        checkedOut = `${dateToString('shortDate', item.ckout)} ${dateToString('timeOnly24', item.ckout)}`
    }

    let remainingTime;
    if (item.remainmins === "done") {
        remainingTime = `<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Checked out</span>`;
    } else if (item.remainmins === "unlimited") {
        remainingTime = `<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-blue-200 text-gray-800">Unlimited</span>`;
    } else {
        remainingTime = item.remainmins;
    }

    let checkStatus;
    if(item.status === 'overdue') {
        checkStatus = `<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800">Overdue</span>`;
    } else if(item.status === 'due') {
        checkStatus = `<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Due</span>`;
    } else if(item.status === 'normal') {
        checkStatus = `<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Due</span>`;
    }

    return {
        childName,
        parentName,
        durationHours,
        checkedIn,
        checkedOut,
        remainingTime,
        checkStatus,
        orderCode: item.ord_code_ph,
        qrChild: item.qr_child,
        qrGuardian: item.qr_guardian,
    };
}

const tableRow = (item) => {

    return `
        <tr class="data-row cursor-pointer hover:bg-gray-100 transition">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 sticky left-0 bg-white z-10">
                ${item.childName}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 sticky left-0 bg-white z-10">
                ${item.parentName}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${item.orderCode}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${item.qrChild}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${item.qrGuardian}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${item.durationHours}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${item.checkedIn}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${item.remainingTime}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${item.checkStatus}
            </td>
        </tr>
    `;
}

const startLoop = () => {
    showConsole("log", "Loop started");

    refreshInterval = setInterval(async () => {
        showConsole("log", "Loop running");
        await displayData(pageState);
    }, 5000);
};

const stopLoop = () => {
    showConsole('log', 'Loop stopped')
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

const displayData = async (page) => {
    pageState = page
    meta = await getData(searchState, pageState)

    renderTableRows(meta.data)

    renderPagination(
        meta,
        async (selectedPage) =>
           await handlePaginationCallback(selectedPage),
        true,
    );
}

const handlePaginationCallback = async (page) => {
    dataRowsBody.innerHTML = tableSkeleton();

    stopLoop();
    disableButtons(true);

    try {
        await displayData(page);
    } catch (err) {
        showConsole("error", err);
        App.component.criticalAlert("Application error");
    } finally {
        startLoop();
        disableButtons(false);
    }
}

const handleSearch = async () => {
    if(!searchState && !searchIn.value.trim()) return

    dataRowsBody.innerHTML = tableSkeleton();
    stopLoop();
    disableButtons(true);

    try {
        searchState = searchIn.value.trim()
        pageState = 1
        await displayData(pageState)
    } catch (err) {
        showConsole('error', err)
        App.component.criticalAlert('Server error')
    } finally {
        startLoop()
        disableButtons(false)
    }

}

const getData = async(search = null, page) => {
    try {
        const response = await api.get(`/api/get-inhouse?search=${search}&page=${page}`)

        if(!response.data.success) {
            App.component.showAlert(response.data.message, 'error')
            return
        }

        return response.data.meta

    } catch (err) {
        App.component.criticalAlert('Server Error')
        showConsole("error", err);
    }
}

const disableButtons = (disable) => {
    const btns = document.querySelectorAll('button')

    btns.forEach(btn => {
        btn.disabled = disable
    })
}

const onMount = () => {
    searchIn = document.getElementById('search-it')
    searchBtn = document.getElementById('filter-btn')
    dataRowsBody = document.getElementById("data-rows");
}

const attachEventListeners = () => {
    searchBtn.addEventListener('click', handleSearch)
}

const nextTick = () => {

}

const init = () => {
    onMount()
    attachEventListeners()
    displayData()
    startLoop()
    nextTick()
}

const destroy = () => {
    if (searchBtn) {
        searchBtn.removeEventListener('click', handleSearch)
    }
}

document.addEventListener('DOMContentLoaded', init);
window.addEventListener('beforeunload',  destroy)