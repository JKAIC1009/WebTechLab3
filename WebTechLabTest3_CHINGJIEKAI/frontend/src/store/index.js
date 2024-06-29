import { createStore } from 'vuex'
import axios from 'axios'

const API_BASE_URL = 'http://localhost:8088'

export default createStore({
  state: {
    users: [],
    error: null
  },
  mutations: {
    setUsers(state, users) {
      state.users = users
    },
    addUser(state, user) {
      state.users.push(user)
    },
    updateUser(state, updatedUser) {
      const index = state.users.findIndex(user => user.id === updatedUser.id)
      if (index !== -1) {
        state.users.splice(index, 1, updatedUser)
      }
    },
    deleteUser(state, userId) {
      state.users = state.users.filter(user => user.id !== userId)
    },
    setError(state, error) {
      state.error = error
    }
  },
  actions: {
    async fetchUsers({ commit }) {
      try {
        const response = await axios.get(`${API_BASE_URL}/users`)
        commit('setUsers', response.data)
      } catch (error) {
        console.error('Error fetching users:', error)
        commit('setError', 'Failed to fetch users')
      }
    },
    async createUser({ commit }, userData) {
        try {
          console.log('Creating user with data:', userData);
          const response = await axios.post(`${API_BASE_URL}/users`, userData)
          commit('addUser', response.data)
        } catch (error) {
          console.error('Error creating user:', error.response ? error.response.data : error.message);
          commit('setError', 'Failed to create user')
          throw error
        }
      },
      async updateUser({ commit }, userData) {
        try {
          console.log('Updating user with data:', userData);
          const response = await axios.put(`${API_BASE_URL}/users/${userData.id}`, userData)
          commit('updateUser', response.data)
        } catch (error) {
          console.error('Error updating user:', error.response ? error.response.data : error.message);
          commit('setError', 'Failed to update user')
          throw error
        }
      },
    async deleteUser({ commit }, userId) {
      try {
        await axios.delete(`${API_BASE_URL}/users/${userId}`)
        commit('deleteUser', userId)
      } catch (error) {
        console.error('Error deleting user:', error)
        commit('setError', 'Failed to delete user')
        throw error
      }
    }
  }
})