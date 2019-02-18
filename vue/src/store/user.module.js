import Vue from 'vue';
import {userService} from '../services';

const initialState = {
    user: {
        username: '',
        lastname: '',
        firstname: '',
        email: '',
        isAdmin: false,
        changePassword: false
    }
};

export const user = {
    namespaced: true,
    state: {
        data: {
            user: initialState.user
        }
    },
    actions: {
        get({commit, getters, state}, {id}) {
            commit('userRequest');

            userService.getUser(id)
                .then(user => commit('getUserSuccess', user))
                .catch(error => commit('userRequestFailure', error));
        },
        update({dispatch, commit}, {user}) {
            commit('userRequest');

            userService.updateUser(user)
                .then(user => commit('updateUserSuccess', user))
                .catch(error => dispatch('alert/error', error.response.data.errors, {root: true}));
        },
        deleteUser({commit}, {user}) {
            commit('userRequest');

            userService.deleteUser(user.id)
                .then(() => commit('reset'))
                .catch(error => commit('userRequestFailure', error));
        },
        create({dispatch, commit}, {user}) {
            commit('userRequest');

            if (user.isAdmin) {
                user.isAdmin = 1;
            } else {
                user.isAdmin = 0;
            }
            if (user.passwordChange) {
                user.passwordChange = 1;
            } else {
                user.passwordChange = 0;
            }

            userService.createUser(user)
                .then(user => commit('createUserSuccess', user))
                .catch(
                    error => {
                        commit('createUserFailure', error.response.data.errors);
                        dispatch('alert/error', error.response.data.errors, {root: true});
                    });
        },
        reset({commit}) {
            commit('reset');
        }
    },
    mutations: {
        userRequest(state) {
            state.data = {loading: true};
        },
        userRequestFailure(state, error) {
            state.data = {error};
        },
        getUserSuccess(state, user) {
            // eslint-disable-next-line
            user.passwordChange = user.passwordChange == 1;
            // eslint-disable-next-line
            user.isAdmin = user.isAdmin == 1;
            state.data = {user};
        },
        updateUserSuccess(state, user) {
            // eslint-disable-next-line
            user.passwordChange = user.passwordChange == 1;
            state.data = {user};
        },
        createUserSuccess(state, user) {
            // eslint-disable-next-line
            user.passwordChange = user.passwordChange == 1;
            state.data = {user};
        },
        createUserFailure(state, error, oldUser) {
            // eslint-disable-next-line
            state.data = {oldUser};
            state.error = error;
        },
        reset(state) {
            Vue.set(state.data, 'user', initialState.user);
        }
    }
};
