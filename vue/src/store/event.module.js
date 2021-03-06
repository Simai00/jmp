import {eventService} from '../services';

export const events = {
    namespaced: true,
    state: {
        all: {},
        overview: {},
        detail: {}
    },
    actions: {
        getAll({commit}) {
            commit('getAllRequest');

            eventService.getAll()
                .then(
                    events => commit('getAllSuccess', events),
                    error => commit('getAllFailure', error)
                );
        },
        getInitialOverview({commit}, {showAll, showElapsed}) {
            commit('getInitialOverviewRequest');

            eventService.getInitialOverview(showAll, showElapsed)
                .then(
                    events => commit('getInitialOverviewSuccess', events),
                    error => commit('getInitialOverviewFailure', error)
                );
        },
        getNextEvents({commit}, {offset, showAll, showElapsed}) {
            if (offset !== 0) {
                commit('getNextEventsRequest');

                eventService.getNextEvents(offset, showAll, showElapsed)
                    .then(
                        events => commit('getNextEventsSuccess', events),
                        error => commit('getNextEventsFailure', error)
                    );
            }
        },
        getEventById({commit}, {eventId}) {
            commit('getEventByIdRequest');

            eventService.getEventById(eventId)
                .then(
                    event => commit('getEventByIdSuccess', event),
                    error => commit('getEventByIdFailure', error)
                );
        },
        getEmptyEvent({commit}) {
            commit('getEmptyEventRequest');

            commit('getEmptyEvent', {'eventType': {}});
        },
        update({dispatch, commit}, {event}) {
            commit('updateEventRequest');

            eventService.update(event)
                .then(event => commit('updateEventSuccess', event))
                .catch(error => {
                    commit('updateEventFailure');
                    dispatch('alert/error', error.response.data.errors, {root: true});
                });
        },
        create({dispatch, commit}, {event}) {
            commit('createEventRequest');

            eventService.create(event)
                .then(event => commit('createEventSuccess', event))
                .catch(error => {
                    commit('createEventFailure');
                    dispatch('alert/error', error.response.data.errors, {root: true});
                });
        }
    },
    mutations: {
        getAllRequest(state) {
            state.all = {loading: true};
        },
        getAllSuccess(state, events) {
            state.all = {events};
            state.all.loading = false;
        },
        getAllFailure(state, error) {
            state.all = {error};
            state.all.loading = false;
        },
        getInitialOverviewRequest(state) {
            state.overview = {loading: true};
        },
        getInitialOverviewSuccess(state, events) {
            state.overview = {events};
            state.overview.loading = false;
        },
        getInitialOverviewFailure(state, error) {
            state.overview = {error};
            state.overview.loading = false;
        },
        getNextEventsRequest(state) {
            state.overview.loading = true;
        },
        getNextEventsSuccess(state, events) {
            if (state.overview.events) {
                state.overview.events.push(...events);
            } else {
                state.overview = {events};
            }
            state.overview.loading = false;
        },
        getNextEventsFailure(state, error) {
            state.overview.error = error;
            state.overview.loading = false;
        },
        getEventByIdRequest(state) {
            state.detail = {loading: true};
        },
        getEmptyEventRequest(state) {
            state.detail = {loading: true};
        },
        getEventByIdSuccess(state, event) {
            state.detail = {event};
            state.detail.loading = false;
        },
        getEmptyEvent(state, event) {
            state.detail = {event};
            state.detail.loading = false;
        },
        getEventByIdFailure(state, error) {
            state.detail = {error};
            state.detail.loading = false;
        },
        updateEventRequest(state) {
            state.detail = {loading: true};
        },
        updateEventSuccess(state, event) {
            state.detail = {event};
            state.detail.loading = false;
        },
        updateEventFailure(state) {
            state.overview.loading = false;
        },
        createEventRequest(state) {
            state.detail = {loading: true};
        },
        createEventSuccess(state, event) {
            state.detail = {event};
            state.detail.loading = false;
        },
        createEventFailure(state) {
            state.overview.loading = false;
        }
    }
};
