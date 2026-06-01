import { useEffect } from 'react';
import { View, ActivityIndicator, StyleSheet } from 'react-native';
import { router, useRootNavigationState } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function Index() {
  const rootNavState = useRootNavigationState();

  useEffect(() => {
    // Wait until the navigator is fully mounted before redirecting
    if (!rootNavState?.key) return;

    const checkAuth = async () => {
      try {
        const token = await AsyncStorage.getItem('userToken');
        if (token === 'logged-in') {
          const role = await AsyncStorage.getItem('userRole');
          if (role === 'MR') {
            router.replace('/(mr)/home');
          } else if (role === 'Stockist') {
            router.replace('/(stockist)/home');
          } else {
            router.replace('/(tabs)');
          }
        } else {
          router.replace('/login');
        }
      } catch (e) {
        router.replace('/login');
      }
    };

    checkAuth();
  }, [rootNavState?.key]);

  return (
    <View style={styles.container}>
      <ActivityIndicator size="large" color="#059669" />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
  },
});
