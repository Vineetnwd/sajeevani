import React, { useState } from 'react';
import { StyleSheet, View, Text, TextInput, TouchableOpacity, Alert, ActivityIndicator, Image, useColorScheme } from 'react-native';
import { router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { SafeAreaView } from 'react-native-safe-area-context';

export default function LoginScreen() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const styles = getStyles(isDark);

  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  // Update this to your network IP for local testing
  const API_URL = 'https://praanveda.net/web/api/auth.php?action=login';

  const handleLogin = async () => {
    if (!phone || !password) {
      Alert.alert('Error', 'Please enter phone and password');
      return;
    }

    setIsLoading(true);

    try {
      const data = new FormData();
      data.append('phone', phone);
      data.append('password', password);

      const response = await fetch(API_URL, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
        },
        body: data,
      });

      const result = await response.json();

      if (result.status === 'success') {
        await AsyncStorage.setItem('userToken', 'logged-in');
        await AsyncStorage.setItem('userId', result.data?.id?.toString() || '1');
        await AsyncStorage.setItem('userRole', result.data?.role || 'Executive');
        await AsyncStorage.setItem('userName', result.data?.name || 'User');

        if (result.data?.role === 'MR') {
          router.replace('/(mr)/home');
        } else if (result.data?.role === 'Doctor') {
          router.replace('/(doctor)/home');
        } else if (result.data?.role === 'Stockist') {
          router.replace('/(stockist)/home');
        } else {
          router.replace('/(tabs)');
        }
      } else {
        Alert.alert('Login Failed', result.message || 'Invalid credentials');
      }
    } catch (error) {
      console.error(error);
      Alert.alert('Network Error', 'Could not connect to the server. Please check your IP and API_URL.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <View style={styles.header}>
          <Image source={require('../assets/images/icon.png')} style={styles.logo} resizeMode="contain" />
          <Text style={styles.title}>Praanveda</Text>
          <Text style={styles.subtitle}>Ayurshakti App</Text>
        </View>

        <View style={styles.form}>
          <TextInput
            style={styles.input}
            placeholder="Phone Number / User ID"
            placeholderTextColor={isDark ? '#9CA3AF' : '#9CA3AF'}
            keyboardType="phone-pad"
            value={phone}
            onChangeText={setPhone}
            autoCapitalize="none"
          />

          <TextInput
            style={styles.input}
            placeholder="Password"
            placeholderTextColor={isDark ? '#9CA3AF' : '#9CA3AF'}
            secureTextEntry
            value={password}
            onChangeText={setPassword}
          />

          <TouchableOpacity style={styles.loginBtn} onPress={handleLogin} disabled={isLoading}>
            {isLoading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.loginBtnText}>Sign In</Text>
            )}
          </TouchableOpacity>
        </View>
      </View>
    </SafeAreaView>
  );
}

const getStyles = (isDark: boolean) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: isDark ? '#111827' : '#F3F4F6',
  },
  content: {
    flex: 1,
    justifyContent: 'center',
    padding: 24,
  },
  header: {
    alignItems: 'center',
    marginBottom: 40,
  },
  logo: {
    width: 100,
    height: 100,
    marginBottom: 16,
  },
  title: {
    fontSize: 36,
    fontWeight: '800',
    color: isDark ? '#10B981' : '#059669',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: isDark ? '#9CA3AF' : '#6B7280',
    fontWeight: '600',
  },
  form: {
    backgroundColor: isDark ? '#1F2937' : '#FFFFFF',
    padding: 24,
    borderRadius: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: isDark ? 0.3 : 0.1,
    shadowRadius: 12,
    elevation: 5,
  },
  input: {
    backgroundColor: isDark ? '#374151' : '#F9FAFB',
    borderWidth: 1,
    borderColor: isDark ? '#4B5563' : '#E5E7EB',
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 14,
    marginBottom: 16,
    fontSize: 16,
    color: isDark ? '#F9FAFB' : '#1F2937',
  },
  loginBtn: {
    backgroundColor: isDark ? '#10B981' : '#059669',
    borderRadius: 10,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 8,
  },
  loginBtnText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
});
