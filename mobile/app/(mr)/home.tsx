import React, { useState, useEffect, useCallback } from 'react';
import {
  StyleSheet, View, Text, ScrollView, TouchableOpacity,
  ActivityIndicator, RefreshControl, StatusBar, Alert, useColorScheme
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
import { router } from 'expo-router';

const API_BASE = 'https://praanveda.net/web/api/mr.php';

const getStatusColors = (isDark: boolean): Record<string, { bg: string; text: string; dot: string }> => ({
  Pending:   { bg: isDark ? '#78350F' : '#FFFBEB', text: isDark ? '#FDE68A' : '#B45309', dot: '#F59E0B' },
  Confirmed: { bg: isDark ? '#1E3A8A' : '#EFF6FF', text: isDark ? '#BFDBFE' : '#1D4ED8', dot: '#3B82F6' },
  Dispatched:{ bg: '#EEF2FF', text: '#4338CA', dot: '#6366F1' },
  Delivered: { bg: isDark ? '#064E3B' : '#F0FDF4', text: isDark ? '#6EE7B7' : '#15803D', dot: '#22C55E' },
  Cancelled: { bg: isDark ? '#7F1D1D' : '#FEF2F2', text: isDark ? '#FECACA' : '#B91C1C', dot: '#EF4444' },
});

export default function MRHomeScreen() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const styles = getStyles(isDark);
  const statusColors = getStatusColors(isDark);

  const [userName, setUserName] = useState('');
  const [mrId, setMrId] = useState('');
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadData = useCallback(async () => {
    try {
      const [name, id] = await Promise.all([
        AsyncStorage.getItem('userName'),
        AsyncStorage.getItem('userId'),
      ]);
      setUserName(name || 'MR');
      setMrId(id || '');

      const res = await fetch(`${API_BASE}?action=my_orders&mr_id=${id}&t=${Date.now()}`);
      const result = await res.json();
      if (result.status === 'success') setOrders(result.data || []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => { loadData(); }, [loadData]);

  const handleLogout = async () => {
    Alert.alert('Sign Out', 'Are you sure you want to sign out?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Sign Out', style: 'destructive',
        onPress: async () => {
          await AsyncStorage.multiRemove(['userToken', 'userId', 'userRole', 'userName']);
          router.replace('/login');
        }
      }
    ]);
  };

  const totalOrders   = orders.length;
  const pendingOrders = orders.filter(o => o.status === 'Pending').length;
  const deliveredOrders = orders.filter(o => o.status === 'Delivered').length;
  const totalRevenue  = orders.reduce((sum, o) => sum + parseFloat(o.total_amount || 0), 0);

  const recentOrders  = orders.slice(0, 3);

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#6366F1" />
      </View>
    );
  }

  return (
    <View style={{ flex: 1, backgroundColor: isDark ? '#022C22' : '#064E3B' }}>
      <SafeAreaView style={{ flex: 1, backgroundColor: isDark ? '#111827' : '#F0FDF4' }} edges={['top']}>
        <StatusBar barStyle="light-content" backgroundColor={isDark ? '#022C22' : '#064E3B'} />
        
        {/* Header */}
        <View style={styles.headerWrapper}>
          <View style={styles.header}>
            <View>
              <Text style={styles.greeting}>Welcome back,</Text>
              <Text style={styles.userName}>{userName}</Text>
              <View style={styles.roleBadge}>
                <Text style={styles.roleBadgeText}>Medical Representative</Text>
              </View>
            </View>
            <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
              <Ionicons name="log-out-outline" size={20} color="#064E3B" />
            </TouchableOpacity>
          </View>
        </View>

        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); loadData(); }} colors={['#10B981']} />}
        >
          {/* Stats Grid */}
          <View style={styles.statsGrid}>
          <View style={styles.statCard}>
            <MaterialCommunityIcons name="clipboard-list-outline" size={24} color="#059669" />
            <Text style={[styles.statNum, { color: '#059669' }]}>{totalOrders}</Text>
            <Text style={styles.statLabel}>Total Orders</Text>
          </View>
          <View style={styles.statCard}>
            <MaterialCommunityIcons name="clock-outline" size={24} color="#D97706" />
            <Text style={[styles.statNum, { color: '#D97706' }]}>{pendingOrders}</Text>
            <Text style={styles.statLabel}>Pending</Text>
          </View>
          <View style={styles.statCard}>
            <MaterialCommunityIcons name="check-decagram-outline" size={24} color="#059669" />
            <Text style={[styles.statNum, { color: '#059669' }]}>{deliveredOrders}</Text>
            <Text style={styles.statLabel}>Delivered</Text>
          </View>
          <View style={styles.statCard}>
            <MaterialCommunityIcons name="currency-inr" size={24} color="#047857" />
            <Text style={[styles.statNum, { color: '#047857' }]}>₹{(totalRevenue / 1000).toFixed(1)}k</Text>
            <Text style={styles.statLabel}>Revenue</Text>
          </View>
        </View>

        {/* Quick Action */}
        <TouchableOpacity style={styles.newOrderCta} onPress={() => router.push('/(mr)/new_order')}>
          <Ionicons name="add-circle-outline" size={22} color="#fff" style={{ marginRight: 10 }} />
          <Text style={styles.newOrderCtaText}>Place New Doctor Order</Text>
          <Ionicons name="chevron-forward" size={18} color="rgba(255,255,255,0.7)" style={{ flex: 1, textAlign: 'right' }} />
        </TouchableOpacity>

        {/* Recent Orders */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Recent Orders</Text>
          <TouchableOpacity onPress={() => router.push('/(mr)/my_orders')}>
            <Text style={styles.seeAllLink}>See all</Text>
          </TouchableOpacity>
        </View>

        {recentOrders.length === 0 ? (
          <View style={styles.emptyBox}>
            <Ionicons name="document-text-outline" size={48} color="#D1D5DB" />
            <Text style={styles.emptyText}>No orders yet</Text>
            <Text style={styles.emptySubtext}>Tap "Place New Doctor Order" to get started.</Text>
          </View>
        ) : (
          recentOrders.map((order) => {
            const sc = statusColors[order.status] || statusColors['Pending'];
            return (
              <View key={order.id} style={styles.orderCard}>
                <View style={styles.orderCardTop}>
                  <View>
                    <Text style={styles.orderIdText}>#DO-{order.id}</Text>
                    <Text style={styles.doctorName}>Dr. {order.doctor_name}</Text>
                  </View>
                  <View style={[styles.statusBadge, { backgroundColor: sc.bg }]}>
                    <View style={[styles.statusDot, { backgroundColor: sc.dot }]} />
                    <Text style={[styles.statusText, { color: sc.text }]}>{order.status}</Text>
                  </View>
                </View>
                <View style={styles.orderCardBottom}>
                  <Text style={styles.orderMeta}>{order.item_count} item{order.item_count !== 1 ? 's' : ''}</Text>
                  <Text style={styles.orderMeta}>•</Text>
                  <Text style={styles.orderMeta}>{new Date(order.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })}</Text>
                  <Text style={styles.orderAmount}>₹{parseFloat(order.total_amount).toFixed(2)}</Text>
                </View>
              </View>
            );
          })
        )}
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}

const getStyles = (isDark: boolean) => StyleSheet.create({
  container: { flex: 1, backgroundColor: isDark ? '#111827' : '#F0FDF4' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: isDark ? '#111827' : '#F0FDF4' },
  scroll: { paddingBottom: 20 },

  headerWrapper: {
    backgroundColor: isDark ? '#022C22' : '#064E3B',
    paddingHorizontal: 20,
    paddingBottom: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
    elevation: 0,
    zIndex: 10,
    marginTop: -50,
    paddingTop: 50 + 16,
  },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  greeting: { fontSize: 13, color: isDark ? '#34D399' : '#A7F3D0', fontWeight: '500' },
  userName: { fontSize: 22, fontWeight: '800', color: '#FFFFFF', letterSpacing: -0.5, marginTop: 2 },
  roleBadge: { backgroundColor: 'rgba(255,255,255,0.2)', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6, alignSelf: 'flex-start', marginTop: 6 },
  roleBadgeText: { fontSize: 10, fontWeight: '700', color: '#ECFDF5', textTransform: 'uppercase' },
  logoutBtn: { width: 36, height: 36, borderRadius: 18, backgroundColor: isDark ? '#065F46' : '#D1FAE5', justifyContent: 'center', alignItems: 'center' },

  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', marginTop: 20, paddingHorizontal: 16, marginBottom: 20 },
  statCard: { flex: 1, minWidth: '40%', backgroundColor: isDark ? '#1F2937' : '#FFFFFF', borderRadius: 16, paddingVertical: 12, paddingHorizontal: 8, alignItems: 'center', margin: 4, elevation: 3, shadowColor: '#000', shadowOpacity: isDark ? 0.3 : 0.05, shadowRadius: 5, shadowOffset: { width: 0, height: 2 } },
  statNum: { fontSize: 22, fontWeight: '800', marginVertical: 4 },
  statLabel: { fontSize: 10, fontWeight: '700', color: isDark ? '#9CA3AF' : '#64748B', textTransform: 'uppercase' },

  newOrderCta: {
    backgroundColor: isDark ? '#10B981' : '#059669', borderRadius: 16, padding: 16, marginHorizontal: 16,
    flexDirection: 'row', alignItems: 'center', marginBottom: 28,
    shadowColor: isDark ? '#10B981' : '#059669', shadowOpacity: 0.3, shadowRadius: 8, shadowOffset: { width: 0, height: 4 }, elevation: 4,
  },
  newOrderCtaText: { color: '#fff', fontSize: 15, fontWeight: '700' },

  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, marginBottom: 12 },
  sectionTitle: { fontSize: 16, fontWeight: '800', color: isDark ? '#10B981' : '#064E3B' },
  seeAllLink: { fontSize: 13, color: isDark ? '#34D399' : '#059669', fontWeight: '700' },

  orderCard: { backgroundColor: isDark ? '#1F2937' : '#FFFFFF', borderRadius: 16, padding: 16, marginHorizontal: 16, marginBottom: 12, elevation: 2, shadowColor: '#000', shadowOpacity: isDark ? 0.3 : 0.05, shadowRadius: 5, shadowOffset: { width: 0, height: 2 } },
  orderCardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  orderIdText: { fontSize: 11, fontWeight: '700', color: isDark ? '#6B7280' : '#94A3B8' },
  doctorName: { fontSize: 14, fontWeight: '700', color: isDark ? '#F9FAFB' : '#0F172A', marginTop: 2 },
  statusBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8 },
  statusDot: { width: 6, height: 6, borderRadius: 3, marginRight: 6 },
  statusText: { fontSize: 11, fontWeight: '700' },
  orderCardBottom: { flexDirection: 'row', alignItems: 'center', marginTop: 6, paddingTop: 10, borderTopWidth: 1, borderTopColor: isDark ? '#374151' : '#F1F5F9' },
  orderMeta: { fontSize: 11, color: isDark ? '#9CA3AF' : '#64748B', marginRight: 6, fontWeight: '500' },
  orderAmount: { fontSize: 15, fontWeight: '800', color: isDark ? '#34D399' : '#059669', flex: 1, textAlign: 'right' },

  emptyBox: { alignItems: 'center', paddingVertical: 40 },
  emptyText: { fontSize: 16, fontWeight: '700', color: isDark ? '#D1D5DB' : '#475569', marginTop: 12 },
  emptySubtext: { fontSize: 12, color: isDark ? '#6B7280' : '#94A3B8', marginTop: 4 },
});
