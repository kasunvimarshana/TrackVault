import React from 'react';
import { StatusBar } from 'expo-status-bar';
import { StyleSheet, Text, View, ActivityIndicator } from 'react-native';
import { AuthProvider, useAuth } from './src/application/store/AuthContext';

function MainApp() {
  const { isAuthenticated, loading, user } = useAuth();

  if (loading) {
    return (
      <View style={styles.container}>
        <ActivityIndicator size="large" color="#007AFF" />
        <Text style={styles.loadingText}>Loading TrackVault...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>TrackVault</Text>
      <Text style={styles.subtitle}>Data Collection & Payment Management</Text>
      
      {isAuthenticated ? (
        <View style={styles.content}>
          <Text style={styles.welcomeText}>Welcome, {user?.name}!</Text>
          <Text style={styles.infoText}>App is ready to use</Text>
        </View>
      ) : (
        <View style={styles.content}>
          <Text style={styles.infoText}>Please login to continue</Text>
        </View>
      )}
      
      <StatusBar style="auto" />
    </View>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <MainApp />
    </AuthProvider>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F2F2F7',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 20,
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#007AFF',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: '#8E8E93',
    marginBottom: 40,
    textAlign: 'center',
  },
  content: {
    alignItems: 'center',
    marginTop: 20,
  },
  welcomeText: {
    fontSize: 20,
    fontWeight: '600',
    color: '#000000',
    marginBottom: 8,
  },
  infoText: {
    fontSize: 16,
    color: '#8E8E93',
    textAlign: 'center',
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: '#8E8E93',
  },
});
